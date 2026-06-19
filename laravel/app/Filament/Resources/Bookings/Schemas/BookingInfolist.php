<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
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
                Section::make('Booking')
                    ->schema([
                        TextEntry::make('booking_code')->label('Code'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'new' => 'warning',
                                'contacted' => 'info',
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('travel_date')->date()->placeholder('-'),
                        TextEntry::make('package_title')
                            ->label('Package')
                            ->state(fn (Booking $record): string => $record->tourPackage
                                ? PublicSite::localized($record->tourPackage->title, 'us', $record->tourPackage->slug)
                                : '-'),
                        TextEntry::make('destination.name')->label('Destination')->placeholder('-'),
                        TextEntry::make('pax')->numeric()->label('Pax'),
                        TextEntry::make('pickup')->placeholder('-'),
                        TextEntry::make('traveler_type')->badge(),
                        TextEntry::make('currency')->badge(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('name')->placeholder('-'),
                        TextEntry::make('email')->label('Email address')->placeholder('-')->copyable(),
                        TextEntry::make('whatsapp')->placeholder('-')->copyable(),
                        TextEntry::make('notes')->placeholder('-')->columnSpanFull(),
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
