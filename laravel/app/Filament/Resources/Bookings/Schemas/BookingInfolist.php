<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Support\BookingAttention;
use App\Models\Booking;
use App\Support\BookingLanguage;
use App\Support\PublicSite;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Follow-up')
                    ->schema([
                        TextEntry::make('attention')
                            ->label('Attention')
                            ->badge()
                            ->state(fn (Booking $record): string => BookingAttention::status($record))
                            ->color(fn (Booking $record): string => BookingAttention::color($record)),
                        TextEntry::make('attention_summary')
                            ->label('Needs')
                            ->state(fn (Booking $record): string => BookingAttention::summary($record)),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'new' => 'warning',
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('confirmed_at')->dateTime()->placeholder('-'),
                        TextEntry::make('cancelled_at')->dateTime()->placeholder('-'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('-'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Payment')
                    ->schema([
                        TextEntry::make('latestPayment.status')
                            ->label('Payment status')
                            ->badge()
                            ->placeholder('Not requested')
                            ->color(fn (?string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending', 'invoice_sent' => 'warning',
                                'failed', 'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('latestPayment.midtrans_transaction_status')
                            ->label('Midtrans status')
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.midtrans_payment_type')
                            ->label('Payment type')
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.quote_amount')
                            ->label('Quote')
                            ->state(fn (Booking $record): string => $record->latestPayment
                                ? PublicSite::formatMoney($record->latestPayment->quote_amount, $record->latestPayment->quote_currency)
                                : '-'),
                        TextEntry::make('latestPayment.exchange_rate')
                            ->label('USD to IDR rate')
                            ->state(fn (Booking $record): string => $record->latestPayment?->exchange_rate
                                ? PublicSite::formatMoney($record->latestPayment->exchange_rate, 'IDR')
                                : '-'),
                        TextEntry::make('latestPayment.exchange_rate_snapshot.source')
                            ->label('Rate source')
                            ->state(fn (Booking $record): string => data_get($record->latestPayment?->exchange_rate_snapshot, 'source', '-'))
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.exchange_rate_snapshot.raw_rate')
                            ->label('Raw rate')
                            ->state(fn (Booking $record): string => data_get($record->latestPayment?->exchange_rate_snapshot, 'raw_rate')
                                ? PublicSite::formatMoney((float) data_get($record->latestPayment?->exchange_rate_snapshot, 'raw_rate'), 'IDR')
                                : '-')
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.exchange_rate_snapshot.buffer_percent')
                            ->label('Rate buffer')
                            ->state(fn (Booking $record): string => data_get($record->latestPayment?->exchange_rate_snapshot, 'buffer_percent') !== null
                                ? data_get($record->latestPayment?->exchange_rate_snapshot, 'buffer_percent').'%'
                                : '-')
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.exchange_rate_snapshot.manual_override')
                            ->label('Manual override')
                            ->state(fn (Booking $record): string => data_get($record->latestPayment?->exchange_rate_snapshot, 'manual_override') ? 'Yes' : ($record->latestPayment?->exchange_rate_snapshot ? 'No' : '-'))
                            ->placeholder('-'),
                        TextEntry::make('latestPayment.charge_amount')
                            ->label('Midtrans charge')
                            ->state(fn (Booking $record): string => $record->latestPayment
                                ? PublicSite::formatMoney($record->latestPayment->charge_amount, 'IDR')
                                : '-'),
                        TextEntry::make('latestPayment.expires_at')->label('Expires at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.sent_at')->label('Invoice sent at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.whatsapp_opened_at')->label('WhatsApp opened/prepared at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.whatsapp_sent_at')->label('WhatsApp sent at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.whatsapp_failed_at')->label('WhatsApp failed at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.whatsapp_error')->label('WhatsApp error')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('latestPayment.paid_at')->label('Paid at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_email_sent_at')->label('Receipt email sent at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_email_failed_at')->label('Receipt email failed at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_email_error')->label('Receipt email error')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('latestPayment.receipt_whatsapp_sent_at')->label('Receipt WhatsApp sent at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_whatsapp_opened_at')->label('Manual receipt WhatsApp opened at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_whatsapp_failed_at')->label('Receipt WhatsApp failed at')->dateTime()->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_whatsapp_provider_message_id')->label('Receipt WhatsApp message ID')->placeholder('-'),
                        TextEntry::make('latestPayment.receipt_whatsapp_error')->label('Receipt WhatsApp error')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('payment_failure_reason')
                            ->label('Failure reason')
                            ->state(fn (Booking $record): string => self::paymentFailureReason($record))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('latestPayment.snap_url')->label('Snap URL')->copyable()->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('booking_code')->label('Code')->copyable(),
                        TextEntry::make('name')->placeholder('-'),
                        TextEntry::make('email')->label('Email address')->placeholder('-')->copyable(),
                        TextEntry::make('whatsapp')->placeholder('-')->copyable(),
                        TextEntry::make('communication_language')->label('Communication language')->badge()->formatStateUsing(fn (?string $state): string => BookingLanguage::label($state)),
                        TextEntry::make('notes')->label('Internal notes')->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Trip request')
                    ->schema([
                        TextEntry::make('package_title')
                            ->label('Package')
                            ->state(fn (Booking $record): string => $record->tourPackage
                                ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug)
                                : '-'),
                        TextEntry::make('destination.name')->label('Destination')->placeholder('-'),
                        TextEntry::make('travel_date')->date()->placeholder('-'),
                        TextEntry::make('pax')->numeric()->label('Pax'),
                        TextEntry::make('pickup')->placeholder('-'),
                        TextEntry::make('traveler_type')->badge(),
                        TextEntry::make('currency')->badge(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Commercial snapshot')
                    ->schema([
                        TextEntry::make('selected_add_ons')
                            ->label('Add-ons')
                            ->state(fn (Booking $record): array => self::addOnSummary($record))
                            ->bulleted()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('voucher_code')->label('Voucher')->placeholder('-'),
                        TextEntry::make('subtotal')->money(fn (Booking $record): string => $record->currency ?: 'IDR'),
                        TextEntry::make('discount_total')->label('Discount')->money(fn (Booking $record): string => $record->currency ?: 'IDR'),
                        TextEntry::make('total')->money(fn (Booking $record): string => $record->currency ?: 'IDR'),
                        TextEntry::make('payment_gateway')->placeholder('-'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    private static function paymentFailureReason(Booking $record): string
    {
        $payment = $record->latestPayment;

        if (! $payment) {
            return '-';
        }

        $response = $payment->midtrans_raw_response ?? [];
        $notification = $payment->midtrans_raw_notification ?? [];
        $messages = $response['error_messages'] ?? null;

        if (is_array($messages)) {
            return implode(' ', $messages);
        }

        return (string) ($response['local_error'] ?? $notification['status_message'] ?? '-');
    }
    /**
     * @return array<int, string>
     */
    private static function addOnSummary(Booking $record): array
    {
        return collect($record->selected_add_ons ?? [])
            ->map(function (array $addOn): string {
                $title = PublicSite::localized($addOn['title'] ?? [], 'us', $addOn['slug'] ?? 'Add-on');
                $pricing = str($addOn['pricing_type'] ?? 'per_booking')->replace('_', ' ')->toString();

                return "{$title} ({$pricing})";
            })
            ->values()
            ->all();
    }
}