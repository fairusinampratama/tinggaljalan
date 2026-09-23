<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\PlatformLink;
use App\Models\Review;
use App\Models\TourPackage;
use App\Models\Voucher;
use App\Support\InertiaPublicData;
use App\Support\PublicSite;
use App\Support\Seo;
use App\Support\VoucherEligibilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $language = PublicSite::language($request);
        $currency = PublicSite::bookingCurrency($language === 'id' ? 'local' : 'international');
        $promotions = app(VoucherEligibilityService::class)
            ->publicPromotions($currency)
            ->map(function (Voucher $voucher) use ($currency): array {
                $packages = $voucher->tourPackages;

                return [
                    'code' => $voucher->code,
                    'title' => $voucher->public_title ?: ['us' => $voucher->label],
                    'description' => $voucher->public_description ?: [],
                    'discountType' => $voucher->discount_type,
                    'discountValue' => (float) $voucher->discount_value,
                    'currency' => $voucher->currency,
                    'maximumDiscount' => $currency === 'IDR'
                        ? $voucher->maximum_discount_idr
                        : $voucher->maximum_discount_usd,
                    'displayCurrency' => $currency,
                    'endsAt' => $voucher->ends_at?->toDateString(),
                    'ctaUrl' => $packages->count() === 1
                        ? '/routes/'.$packages->first()->slug
                        : '/routes',
                    'packageCount' => $packages->count(),
                ];
            });

        return Inertia::render('HomePage', [
            'language' => $language,
            'destinations' => Destination::query()->active()->featured()->ordered()->get()->map(fn (Destination $destination) => InertiaPublicData::destination($destination))->values(),
            'featuredRoutes' => InertiaPublicData::routeCards(TourPackage::query()->with(['destination', 'newsArticles'])->active()->featured()->ordered()->limit(6)->get()),
            'latestArticles' => InertiaPublicData::articleCards(NewsArticle::query()->with(['destination', 'articleCategory', 'tourPackages'])->published()->latest('published_at')->limit(3)->get()),
            'faqs' => Faq::query()->active()->ordered()->limit(8)->get()->map(fn (Faq $faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->values(),
            'reviews' => Review::query()->active()->featured()->ordered()->limit(Review::MAX_ACTIVE_FEATURED)->get(),
            'promotions' => $promotions,
            'platformLinks' => PlatformLink::query()->active()->ordered()->limit(PlatformLink::MAX_ACTIVE)->get(),
            'seo' => Seo::home($request),
        ]);
    }
}
