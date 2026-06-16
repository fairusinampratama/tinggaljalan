<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Support\AdminForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_code')->required()->maxLength(255),
                Select::make('tour_package_id')
                    ->relationship('tourPackage', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->title['us'] ?? $record->slug)
                    ->searchable()
                    ->preload(),
                Select::make('destination_id')->relationship('destination', 'name')->searchable()->preload(),
                TextInput::make('name'),
                TextInput::make('email')->label('Email address')->email(),
                TextInput::make('whatsapp'),
                DatePicker::make('travel_date'),
                TextInput::make('pax')->required()->numeric()->default(1),
                TextInput::make('pickup'),
                Select::make('traveler_type')->options(['local' => 'Local', 'international' => 'International'])->required()->default('local'),
                Select::make('currency')->options(['IDR' => 'IDR', 'USD' => 'USD'])->required()->default('IDR'),
                AdminForm::json('selected_add_ons', 'Selected add-ons snapshot'),
                TextInput::make('voucher_code'),
                TextInput::make('subtotal')->required()->numeric()->default(0),
                TextInput::make('discount_total')->required()->numeric()->default(0),
                TextInput::make('total')->required()->numeric()->default(0),
                TextInput::make('payment_gateway'),
                Textarea::make('notes')->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->required()
                    ->default('new'),
            ]);
    }
}
