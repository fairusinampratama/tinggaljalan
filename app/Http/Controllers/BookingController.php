<?php

namespace App\Http\Controllers;

use App\Gateways\Email\EmailGatewayService;
use App\Gateways\WhatsApp\NewBookingAdminWhatsAppMessage;
use App\Gateways\WhatsApp\WhatsAppGatewayService;
use App\Jobs\SendNewBookingAdminNotification;
use App\Models\Booking;
use App\Models\TourPackage;
use App\Support\BookingLanguage;
use App\Support\InertiaPublicData;
use App\Support\PhoneNumber;
use App\Support\PublicSite;
use App\Support\Seo;
use App\Support\VoucherEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Propaganistas\LaravelPhone\Rules\Phone;

class BookingController extends Controller
{
    public function create(Request $request)
    {
        $language = PublicSite::language($request);
        $draft = PublicSite::bookingDraft($request);

        if ($request->query('route')) {
            $draft['route'] = $request->query('route');
        }

        $package = PublicSite::packageFromDraft($draft);
        $draft['route'] = $package?->slug;
        $request->session()->put('booking_draft', $draft);

        return Inertia::render('BookingPage', [
            'language' => $language,
            'draft' => $draft,
            'routes' => InertiaPublicData::routes(TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->ordered()->get()),
            'route' => $package ? InertiaPublicData::route($package) : null,
            'booking' => InertiaPublicData::bookingPayload($request, $package, $draft),
            'seo' => Seo::noindex([
                'title' => 'Booking Request | Tinggal Jalan',
                'description' => 'Send a Tinggal Jalan tour booking request with route, date, guests, pickup point, and add-ons.',
                'canonical' => Seo::canonical('/booking'),
            ]),
        ]);
    }

    public function storeDraft(Request $request)
    {
        $data = $request->validate($this->draftRules());
        $data['add_ons'] = $data['add_ons'] ?? [];
        $data['voucher_applied'] = false;

        $package = PublicSite::packageFromDraft($data);
        $summary = PublicSite::bookingSummary($package, $data);

        if ($summary['quote_required']) {
            $request->session()->put('booking_draft', $data);
            $request->session()->forget('errors');

            return redirect()->route('booking.create')->withErrors(['pax' => $this->largeGroupMessage()]);
        }

        $request->session()->put('booking_draft', $data);

        return redirect()->route('checkout.review');
    }

    public function recalculate(Request $request)
    {
        $data = $request->validate($this->draftRules());
        $data['add_ons'] = $data['add_ons'] ?? [];
        $data['voucher'] = app(VoucherEligibilityService::class)->normalizeCode($data['voucher'] ?? null);
        $data['voucher_applied'] = filled($data['voucher'] ?? null);
        $request->session()->put('booking_draft', $data);

        return back();
    }

    public function review(Request $request)
    {
        $language = PublicSite::language($request);
        $draft = PublicSite::bookingDraft($request);
        $package = PublicSite::packageFromDraft($draft);

        return Inertia::render('CheckoutReviewPage', [
            'language' => $language,
            'draft' => $draft,
            'routes' => InertiaPublicData::routes(TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->ordered()->get()),
            'route' => $package ? InertiaPublicData::route($package) : null,
            'booking' => InertiaPublicData::bookingPayload($request, $package, $draft),
            'seo' => Seo::noindex([
                'title' => 'Traveler Contact | Tinggal Jalan',
                'description' => 'Complete traveler contact details for a Tinggal Jalan booking request.',
                'canonical' => Seo::canonical('/checkout/review'),
            ]),
        ]);
    }

    public function submit(Request $request)
    {
        $country = strtoupper(trim((string) $request->input('whatsapp_country', 'ID')));
        $request->merge([
            'whatsapp_country' => $country,
            'whatsapp' => PhoneNumber::normalize($request->input('whatsapp'), $country),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $draft = array_merge(PublicSite::bookingDraft($request), $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_country' => ['required', 'string', 'size:2', Rule::in(array_keys(PhoneNumber::countries()))],
            'whatsapp' => ['required', 'string', (new Phone)->countryField('whatsapp_country')->lenient()],
            'email' => ['required', 'email:rfc', 'max:255'],
            'voucher' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));
        $draft['voucher'] = app(VoucherEligibilityService::class)->normalizeCode($draft['voucher'] ?? null);
        $package = PublicSite::packageFromDraft($draft);
        $availability = PublicSite::availability($package, $draft['date']);

        if (in_array($availability['status'], ['booked', 'blocked'], true)) {
            $message = 'Selected date is not available.'.(filled($availability['reason']) ? ' '.$availability['reason'] : '');

            return back()->withErrors(['date' => $message]);
        }

        $summary = PublicSite::bookingSummary($package, $draft);

        if ($summary['quote_required']) {
            $request->session()->put('booking_draft', $draft);

            return redirect()->route('booking.create')->with('large_group_message', $this->largeGroupMessage());
        }

        $booking = DB::transaction(function () use ($draft, $package, $request): Booking {
            $eligibility = app(VoucherEligibilityService::class)->evaluate(
                $draft['voucher'],
                PublicSite::bookingCurrency($draft['traveler_type'] ?? null),
                lockForUpdate: true,
            );

            if ($draft['voucher'] !== '' && $eligibility['state'] !== VoucherEligibilityService::APPLIED) {
                throw ValidationException::withMessages([
                    'voucher' => 'This voucher is invalid or unavailable for this booking.',
                ]);
            }

            $summary = PublicSite::bookingSummary($package, $draft, $eligibility);

            return Booking::create([
                'booking_code' => PublicSite::bookingCode(),
                'tour_package_id' => $package?->id,
                'destination_id' => $package?->destination_id,
                'name' => $draft['name'],
                'email' => $draft['email'] ?? null,
                'whatsapp' => $draft['whatsapp'],
                'whatsapp_country' => $draft['whatsapp_country'],
                'communication_language' => BookingLanguage::normalize(PublicSite::language($request)),
                'travel_date' => $draft['date'],
                'pax' => $summary['pax'],
                'pickup' => $draft['pickup'] ?? null,
                'traveler_type' => $draft['traveler_type'] ?? 'international',
                'currency' => $summary['currency'],
                'pricing_mode' => $summary['pricing_mode'],
                'pricing_status' => $summary['pricing_status'],
                'price_tier_id' => data_get($summary, 'selected_tier.id'),
                'tier_min_pax' => data_get($summary, 'selected_tier.min_pax'),
                'tier_max_pax' => data_get($summary, 'selected_tier.max_pax'),
                'unit_price' => $summary['unit_price'],
                'package_subtotal' => $summary['package_subtotal'],
                'selected_add_ons' => collect($summary['addOns'])->map(fn ($packageAddOn) => [
                    'id' => $packageAddOn->id,
                    'slug' => (string) $packageAddOn->id,
                    'title' => $packageAddOn->title,
                    'description' => $packageAddOn->description,
                    'price_idr' => $packageAddOn->price_idr,
                    'price_usd' => $packageAddOn->price_usd,
                    'pricing_type' => $packageAddOn->pricing_type,
                ])->values()->toArray(),
                'voucher_code' => $summary['voucher']?->code,
                'subtotal' => $summary['subtotal'] ?? 0,
                'discount_total' => $summary['discount'],
                'total' => $summary['total'] ?? 0,
                'payment_gateway' => $summary['payment_gateway'],
                'notes' => $draft['notes'] ?? null,
                'status' => 'new',
            ]);
        }, attempts: 3);

        $request->session()->put('booking_id', $booking->id);
        $request->session()->put('booking_draft', $draft);

        app()->terminating(function () use ($booking): void {
            app(SendNewBookingAdminNotification::class, ['bookingId' => $booking->id])->handle(
                app(EmailGatewayService::class),
                app(WhatsAppGatewayService::class),
                app(NewBookingAdminWhatsAppMessage::class),
            );
        });

        return redirect()->route('checkout.confirmation');
    }

    public function payment()
    {
        return redirect()->route('checkout.confirmation');
    }

    public function confirmation(Request $request)
    {
        $booking = Booking::query()->with('tourPackage.destination')->find($request->session()->get('booking_id'));

        if (! $booking) {
            return redirect()->route('booking.create');
        }

        $language = BookingLanguage::normalize($booking->communication_language);

        $whatsappUrl = PublicSite::whatsappUrl([
            BookingLanguage::translate('booking.confirmation_help', [], $language),
            BookingLanguage::translate('booking.booking_code', [], $language).": {$booking->booking_code}",
            BookingLanguage::translate('booking.package', [], $language).': '.PublicSite::localized($booking->tourPackage?->title, $language),
            BookingLanguage::translate('booking.travel_date', [], $language).': '.BookingLanguage::date($booking->travel_date, $language),
            BookingLanguage::translate('booking.guests', [], $language).": {$booking->pax}",
            BookingLanguage::translate('booking.pickup', [], $language).": {$booking->pickup}",
            BookingLanguage::translate('booking.total', [], $language).': '.(
                $booking->pricing_status === 'quote_required'
                    ? BookingLanguage::translate('booking.to_be_confirmed', [], $language)
                    : PublicSite::formatMoney($booking->total, $booking->currency)
            ),
        ]);

        $draft = PublicSite::bookingDraft($request);
        $package = $booking->tourPackage;

        return Inertia::render('CheckoutConfirmationPage', [
            'language' => $language,
            'booking' => InertiaPublicData::bookingPayload($request, $package, $draft),
            'savedBooking' => [
                'code' => $booking->booking_code,
                'name' => $booking->name,
                'whatsapp' => $booking->whatsapp,
                'email' => $booking->email,
                'notes' => $booking->notes,
            ],
            'routes' => InertiaPublicData::routes(TourPackage::query()->with(['destination', 'packageAddOns', 'itineraryItems', 'newsArticles'])->active()->ordered()->get()),
            'route' => $package ? InertiaPublicData::route($package) : null,
            'whatsappUrl' => $whatsappUrl,
            'seo' => Seo::noindex([
                'title' => 'Booking Confirmation | Tinggal Jalan',
                'description' => 'Tinggal Jalan booking request confirmation and WhatsApp handoff.',
                'canonical' => Seo::canonical('/checkout/confirmation'),
            ]),
        ]);
    }

    private function largeGroupMessage(): string
    {
        return 'This group size needs manual vehicle, guide, and schedule confirmation. Please contact our team before booking.';
    }

    private function draftRules(): array
    {
        return [
            'route' => ['required', Rule::exists('tour_packages', 'slug')],
            'date' => ['required', 'date'],
            'pax' => ['required', 'integer', 'min:'.config('booking.minimum_guests'), 'max:'.config('booking.maximum_guests')],
            'pickup' => ['nullable', 'string', 'max:255'],
            'traveler_type' => ['required', Rule::in(['local', 'international'])],
            'add_ons' => ['array'],
            'add_ons.*' => ['string'],
            'voucher' => ['nullable', 'string', 'max:255'],
        ];
    }
}
