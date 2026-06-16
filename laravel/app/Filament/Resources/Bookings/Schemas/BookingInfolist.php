<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('booking_code'),
                TextEntry::make('tourPackage.title')
                    ->label('Tour package')
                    ->placeholder('-'),
                TextEntry::make('destination.name')
                    ->label('Destination')
                    ->placeholder('-'),
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('whatsapp')
                    ->placeholder('-'),
                TextEntry::make('travel_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('pax')
                    ->numeric(),
                TextEntry::make('pickup')
                    ->placeholder('-'),
                TextEntry::make('traveler_type'),
                TextEntry::make('currency'),
                TextEntry::make('selected_add_ons')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('voucher_code')
                    ->placeholder('-'),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('discount_total')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('payment_gateway')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
