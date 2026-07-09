<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Filament\Support\BookingAttention;
use App\Filament\Support\BookingPaymentHandoff;
use App\Filament\Support\BookingWorkflow;
use App\Gateways\WhatsApp\WhatsAppGatewayService;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Payments\BookingPaymentService;
use App\Payments\ExchangeRates\ExchangeRateService;
use App\Payments\PaymentReceiptService;
use App\Support\BookingLanguage;
use App\Support\BookingQuoteService;
use App\Support\PhoneNumber;
use App\Support\PublicSite;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['tourPackage', 'destination', 'activePayment', 'latestPayment']))
            ->columns([
                TextColumn::make('booking_summary')
                    ->label('Booking')
                    ->state(fn (Booking $record): string => $record->booking_code)
                    ->description(fn (Booking $record): string => $record->created_at?->diffForHumans() ?? '-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('booking_code', 'like', "%{$search}%"))
                    ->copyable(),
                TextColumn::make('customer_summary')
                    ->label('Customer')
                    ->state(fn (Booking $record): string => $record->name ?: '-')
                    ->description(fn (Booking $record): string => $record->whatsapp ?: ($record->email ?: 'No contact details'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    }))
                    ->wrap(),
                TextColumn::make('communication_language')
                    ->label('Language')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => BookingLanguage::label($state))
                    ->color(fn (?string $state): string => match (BookingLanguage::normalize($state)) {
                        'id' => 'danger',
                        'cn' => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('trip_summary')
                    ->label('Trip')
                    ->state(fn (Booking $record): string => $record->tourPackage
                        ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug)
                        : 'Package missing')
                    ->description(fn (Booking $record): string => ($record->travel_date?->format('M d, Y') ?? 'Date missing').' / '.($record->pax ?: 0).' guests')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'tourPackage',
                        fn (Builder $query): Builder => $query
                            ->where('slug', 'like', "%{$search}%")
                            ->orWhere('title->us', 'like', "%{$search}%"),
                    ))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('travel_date', $direction))
                    ->wrap(),
                TextColumn::make('workflow')
                    ->label('Workflow')
                    ->badge()
                    ->state(fn (Booking $record): string => BookingWorkflow::label($record))
                    ->color(fn (Booking $record): string => BookingWorkflow::color($record))
                    ->description(fn (Booking $record): string => BookingWorkflow::summary($record))
                    ->wrap(),
                TextColumn::make('payment_summary')
                    ->label('Payment')
                    ->state(fn (Booking $record): string => PublicSite::formatMoney((int) $record->total, $record->currency ?: 'IDR'))
                    ->description(fn (Booking $record): string => self::paymentSummary($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total', $direction)),
                TextColumn::make('status')
                    ->label('Booking status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('raw_payment_status')
                    ->label('Raw payment status')
                    ->badge()
                    ->state(fn (Booking $record): string => $record->latestPayment?->status ?? 'not_requested')
                    ->color(fn (Booking $record): string => self::paymentColor($record->latestPayment?->status))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('handoff_status')
                    ->label('Handoff / receipt')
                    ->badge()
                    ->state(fn (Booking $record): string => BookingPaymentHandoff::status($record->latestPayment))
                    ->color(fn (Booking $record): string => BookingPaymentHandoff::color($record->latestPayment))
                    ->description(fn (Booking $record): string => BookingPaymentHandoff::summary($record->latestPayment))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => BookingAttention::applyNeedsAttention($query)),
                Filter::make('upcoming_soon')
                    ->label('Upcoming soon')
                    ->query(fn (Builder $query): Builder => BookingAttention::applyUpcomingSoon($query)),
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('destination_id')->relationship('destination', 'name')->label('Destination'),
                Filter::make('travel_date')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('travel_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('travel_date', '<=', $date))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('open_whatsapp')
                        ->label('Open customer WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (Booking $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->whatsapp))
                        ->visible(fn (Booking $record): bool => filled(preg_replace('/\D+/', '', (string) $record->whatsapp)))
                        ->openUrlInNewTab(),
                ])
                    ->label('Contact')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->button(),
                ActionGroup::make([
                    Action::make('set_final_quote')
                        ->label('Set final group quote')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->visible(fn (Booking $record): bool => $record->pricing_status === 'quote_required')
                        ->schema([
                            TextInput::make('unit_price')
                                ->label(fn (Booking $record): string => "Final package price per person ({$record->currency})")
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ])
                        ->action(function (Booking $record, array $data): void {
                            app(BookingQuoteService::class)->apply($record, (int) $data['unit_price']);
                            Notification::make()->title('Final group quote saved')->success()->send();
                        }),
                    Action::make('create_payment_request')
                        ->label('Create payment request')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->visible(fn (Booking $record): bool => !app(\App\Payments\PaymentSettingsService::class)->isManualActive() && $record->status === 'confirmed' && in_array($record->pricing_status, ['priced', 'quoted'], true) && $record->total > 0 && ! $record->activePayment)
                        ->schema([
                            TextInput::make('exchange_rate')
                                ->label('USD to IDR exchange rate')
                                ->helperText(fn (Booking $record): string => self::exchangeRateHelperText($record))
                                ->default(fn (Booking $record): ?int => self::suggestedExchangeRate($record))
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (Booking $record): bool => ($record->currency ?: 'IDR') === 'USD'),
                        ])
                        ->action(function (Booking $record, array $data): void {
                            try {
                                app(BookingPaymentService::class)->createPaymentRequest($record, filled($data['exchange_rate'] ?? null) ? (int) $data['exchange_rate'] : null);
                            } catch (\Throwable $exception) {
                                Notification::make()
                                    ->title('Payment request failed')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Payment request created')
                                ->body('Send it by email or WhatsApp when you are ready.')
                                ->success()
                                ->send();
                        }),
                    Action::make('create_manual_payment_request')
                        ->label('Create manual payment request')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn (Booking $record): bool => app(\App\Payments\PaymentSettingsService::class)->isManualActive() && $record->status === 'confirmed' && in_array($record->pricing_status, ['priced', 'quoted'], true) && $record->total > 0 && ! $record->activePayment)
                        ->schema([
                            TextInput::make('exchange_rate')
                                ->label('USD to IDR exchange rate')
                                ->helperText(fn (Booking $record): string => self::exchangeRateHelperText($record))
                                ->default(fn (Booking $record): ?int => self::suggestedExchangeRate($record))
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (Booking $record): bool => ($record->currency ?: 'IDR') === 'USD'),
                        ])
                        ->action(function (Booking $record, array $data): void {
                            try {
                                app(BookingPaymentService::class)->createManualPaymentRequest($record, filled($data['exchange_rate'] ?? null) ? (int) $data['exchange_rate'] : null);
                            } catch (\Throwable $exception) {
                                Notification::make()
                                    ->title('Manual payment request failed')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Manual payment request created')
                                ->body('Send it by email or WhatsApp when you are ready.')
                                ->success()
                                ->send();
                        }),
                    Action::make('mark_manual_payment_as_paid')
                        ->label('Mark as paid (Manual Transfer)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Booking $record): bool => $record->activePayment?->provider === 'manual' && self::isPayable($record->activePayment))
                        ->action(function (Booking $record): void {
                            try {
                                app(BookingPaymentService::class)->markManualPaymentAsPaid($record->activePayment);
                            } catch (\Throwable $exception) {
                                Notification::make()
                                    ->title('Failed to mark as paid')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()->title('Payment marked as paid')->success()->send();
                        }),
                    Action::make('send_invoice_email')
                        ->label(fn (Booking $record): string => $record->activePayment?->sent_at ? 'Resend payment request email' : 'Send payment request email')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->visible(fn (Booking $record): bool => filled($record->email) && self::isPayable($record->activePayment))
                        ->action(function (Booking $record): void {
                            try {
                                app(BookingPaymentService::class)->sendInvoice($record->activePayment);
                            } catch (\Throwable $exception) {
                                Notification::make()
                                    ->title('Payment request email failed')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()->title('Payment request email sent')->success()->send();
                        }),
                    Action::make('send_payment_whatsapp')
                        ->label(fn (Booking $record): string => ($record->activePayment?->whatsapp_sent_at || $record->activePayment?->whatsapp_opened_at) ? 'Resend payment request WhatsApp' : 'Send payment request WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->visible(fn (Booking $record): bool => filled($record->whatsapp) && self::isPayable($record->activePayment))
                        ->action(function (Booking $record) {
                            $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($record->activePayment);

                            if ($result->sent) {
                                Notification::make()
                                    ->title('WhatsApp sent via Whatspie')
                                    ->body($result->providerMessageId ? "Provider message ID: {$result->providerMessageId}" : null)
                                    ->success()
                                    ->send();

                                return null;
                            }

                            if ($result->manualFallback && $result->redirectUrl) {
                                Notification::make()
                                    ->title($result->error ? 'Whatspie failed, manual WhatsApp opened' : 'Manual WhatsApp opened')
                                    ->body($result->error)
                                    ->warning()
                                    ->send();

                                return redirect()->away($result->redirectUrl);
                            }

                            Notification::make()
                                ->title('WhatsApp failed')
                                ->body($result->error ?: 'Unable to send WhatsApp payment request.')
                                ->danger()
                                ->send();

                            return null;
                        }),
                    Action::make('open_manual_payment_whatsapp')
                        ->label('Open manual payment request WhatsApp')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn (Booking $record): bool => filled($record->whatsapp) && self::isPayable($record->activePayment))
                        ->action(function (Booking $record) {
                            $result = app(WhatsAppGatewayService::class)->sendPaymentRequest($record->activePayment, forceManual: true);

                            Notification::make()
                                ->title('Manual WhatsApp opened')
                                ->success()
                                ->send();

                            return $result->redirectUrl ? redirect()->away($result->redirectUrl) : null;
                        }),
                    Action::make('resend_receipt_email')
                        ->label(fn (Booking $record): string => $record->latestPayment?->receipt_email_sent_at ? 'Resend payment receipt email' : 'Send payment receipt email')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->visible(fn (Booking $record): bool => $record->latestPayment?->status === 'paid' && filled($record->email))
                        ->action(function (Booking $record): void {
                            $result = app(PaymentReceiptService::class)->sendEmail($record->latestPayment);

                            Notification::make()
                                ->title($result->success ? 'Receipt email sent' : 'Receipt email failed')
                                ->body($result->error)
                                ->color($result->success ? 'success' : 'danger')
                                ->send();
                        }),
                    Action::make('resend_receipt_whatsapp')
                        ->label(fn (Booking $record): string => $record->latestPayment?->receipt_whatsapp_sent_at ? 'Resend payment receipt WhatsApp' : 'Send payment receipt WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->visible(fn (Booking $record): bool => $record->latestPayment?->status === 'paid' && filled($record->whatsapp))
                        ->action(function (Booking $record) {
                            $result = app(PaymentReceiptService::class)->sendWhatsApp($record->latestPayment);

                            if ($result->success) {
                                Notification::make()
                                    ->title('Payment receipt WhatsApp sent')
                                    ->body($result->providerMessageId ? "Provider message ID: {$result->providerMessageId}" : null)
                                    ->success()
                                    ->send();

                                return null;
                            }

                            Notification::make()
                                ->title($result->manualFallback ? 'Manual payment receipt WhatsApp required' : 'Payment receipt WhatsApp failed')
                                ->body($result->error)
                                ->warning()
                                ->send();

                            return null;
                        }),
                    Action::make('open_manual_receipt_whatsapp')
                        ->label('Open manual receipt WhatsApp')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn (Booking $record): bool => $record->latestPayment?->status === 'paid' && filled($record->whatsapp))
                        ->action(function (Booking $record) {
                            $result = app(PaymentReceiptService::class)->sendWhatsApp($record->latestPayment, forceManual: true);

                            Notification::make()
                                ->title('Manual receipt WhatsApp opened')
                                ->success()
                                ->send();

                            return $result->redirectUrl ? redirect()->away($result->redirectUrl) : null;
                        }),
                    Action::make('sync_payment_status')
                        ->label('Sync Midtrans status')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (Booking $record): bool => (bool) $record->activePayment)
                        ->action(function (Booking $record): void {
                            app(BookingPaymentService::class)->sync($record->activePayment);

                            Notification::make()->title('Payment status synced')->success()->send();
                        }),
                    Action::make('cancel_payment_request')
                        ->label('Cancel payment request')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Booking $record): bool => self::isPayable($record->activePayment))
                        ->action(function (Booking $record): void {
                            app(BookingPaymentService::class)->cancel($record->activePayment);

                            Notification::make()->title('Payment request cancelled')->success()->send();
                        }),
                ])
                    ->label('Payment')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->button(),
                ActionGroup::make([
                    Action::make('mark_confirmed')
                        ->label('Confirm availability')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Booking $record): bool => $record->status === 'new')
                        ->action(function (Booking $record): void {
                            BookingAttention::transitionTo($record, 'confirmed');
                        }),
                    Action::make('mark_completed')
                        ->label('Mark trip completed')
                        ->icon('heroicon-o-flag')
                        ->color('gray')
                        ->visible(fn (Booking $record): bool => $record->status === 'confirmed')
                        ->action(function (Booking $record): void {
                            BookingAttention::transitionTo($record, 'completed');
                        }),
                    Action::make('cancel')
                        ->label('Cancel booking')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Booking $record): bool => ! in_array($record->status, ['cancelled', 'completed'], true))
                        ->action(function (Booking $record): void {
                            BookingAttention::transitionTo($record, 'cancelled');
                        }),
                ])
                    ->label('Booking status')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->button(),
                Action::make('correct_details')
                    ->label('Correct details')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->visible(fn (Booking $record): bool => ! in_array($record->status, ['cancelled', 'completed'], true))
                    ->modalHeading('Correct booking details')
                    ->modalDescription('Update contact and trip logistics only. Pricing, package, pax, currency, payment, and status remain unchanged.')
                    ->fillForm(fn (Booking $record): array => [
                        'name' => $record->name,
                        'whatsapp_country' => $record->whatsapp_country ?? PhoneNumber::detectCountry($record->whatsapp) ?? 'ID',
                        'whatsapp' => $record->whatsapp,
                        'communication_language' => BookingLanguage::normalize($record->communication_language),
                        'email' => $record->email,
                        'travel_date' => $record->travel_date,
                        'pickup' => $record->pickup,
                        'notes' => $record->notes,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Customer name')
                            ->required()
                            ->maxLength(255),
                        Select::make('whatsapp_country')
                            ->label('WhatsApp country')
                            ->options(PhoneNumber::countries())
                            ->searchable()
                            ->required(),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(30)
                            ->helperText('Enter a local number for the selected country, or a complete international number.'),
                        Select::make('communication_language')
                            ->label('Communication language')
                            ->options(BookingLanguage::OPTIONS)
                            ->helperText('Used for future customer pages, email, and WhatsApp messages.'),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('travel_date')
                            ->label('Travel date')
                            ->required(),
                        TextInput::make('pickup')
                            ->label('Pickup point')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        $data['whatsapp_country'] = strtoupper(trim((string) ($data['whatsapp_country'] ?? 'ID')));
                        $data['whatsapp'] = PhoneNumber::normalize($data['whatsapp'] ?? null, $data['whatsapp_country']);
                        $data['email'] = strtolower(trim((string) ($data['email'] ?? '')));
                        $data['communication_language'] = BookingLanguage::normalize($data['communication_language'] ?? $record->communication_language);

                        $validated = validator($data, [
                            'name' => ['required', 'string', 'max:255'],
                            'whatsapp_country' => ['required', 'string', 'size:2', Rule::in(array_keys(PhoneNumber::countries()))],
                            'whatsapp' => ['required', (new Phone)->countryField('whatsapp_country')->lenient()],
                            'email' => ['required', 'email:rfc', 'max:255'],
                            'communication_language' => ['required', Rule::in(array_keys(BookingLanguage::OPTIONS))],
                            'travel_date' => ['required', 'date'],
                            'pickup' => ['nullable', 'string', 'max:255'],
                            'notes' => ['nullable', 'string', 'max:2000'],
                        ])->validate();

                        $record->update($validated);

                        Notification::make()
                            ->title('Booking details corrected')
                            ->body('Pricing, payment, and booking status were not changed.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }

    private static function isPayable(?BookingPayment $payment): bool
    {
        return $payment && in_array($payment->status, ['pending', 'invoice_sent'], true);
    }

    private static function paymentSummary(Booking $booking): string
    {
        $payment = $booking->latestPayment;

        if (! $payment) {
            return 'Not requested';
        }

        if ($payment->status === 'paid') {
            if ($payment->receipt_email_failed_at || $payment->receipt_whatsapp_failed_at) {
                return 'Paid / receipt issue';
            }

            $emailSent = filled($payment->receipt_email_sent_at);
            $whatsappSent = filled($payment->receipt_whatsapp_sent_at);
            $automatic = filled($payment->receipt_notifications_attempted_at);

            if ($emailSent && $whatsappSent) {
                return $automatic ? 'Paid / receipt auto-sent by email + WA' : 'Paid / receipt sent by email + WA';
            }

            if ($emailSent) {
                return $automatic ? 'Paid / receipt auto-sent by email' : 'Paid / receipt email sent';
            }

            if ($whatsappSent) {
                return $automatic ? 'Paid / receipt auto-sent by WA' : 'Paid / receipt WA sent';
            }

            return $automatic ? 'Paid / automatic receipt not delivered' : 'Paid / receipt not sent';
        }

        if (in_array($payment->status, ['failed', 'expired', 'cancelled'], true)) {
            return 'Payment issue';
        }

        if (in_array($payment->status, ['pending', 'invoice_sent'], true)) {
            $handedOff = filled($payment->sent_at)
                || filled($payment->whatsapp_sent_at)
                || filled($payment->whatsapp_opened_at);

            return $handedOff ? 'Awaiting payment' : 'Ready to send';
        }

        return ucfirst(str_replace('_', ' ', $payment->status));
    }

    private static function paymentColor(?string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'pending', 'invoice_sent' => 'warning',
            'expired' => 'gray',
            'failed', 'cancelled' => 'danger',
            default => 'gray',
        };
    }

    private static function suggestedExchangeRate(Booking $record): ?int
    {
        if (($record->currency ?: 'IDR') !== 'USD') {
            return null;
        }

        return app(ExchangeRateService::class)->previewUsdToIdr()?->finalRate;
    }

    private static function exchangeRateHelperText(Booking $record): string
    {
        if (($record->currency ?: 'IDR') !== 'USD') {
            return 'Midtrans will charge the booking total in IDR.';
        }

        $quote = app(ExchangeRateService::class)->previewUsdToIdr();

        if (! $quote) {
            return 'Auto rate is unavailable right now. Enter a manual USD to IDR rate to create this payment request.';
        }

        $cache = $quote->fromCache ? 'cached ' : '';
        $raw = PublicSite::formatMoney($quote->rawRate, 'IDR');
        $final = PublicSite::formatMoney($quote->finalRate, 'IDR');
        $time = $quote->fetchedAt->format('M d, Y H:i');

        return "Suggested {$cache}rate from {$quote->source}: raw {$raw}, buffer {$quote->bufferPercent}%, final {$final}. Fetched {$time}. You may override before creating the payment.";
    }
}
