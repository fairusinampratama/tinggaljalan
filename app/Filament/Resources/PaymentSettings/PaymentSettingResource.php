<?php

namespace App\Filament\Resources\PaymentSettings;

use App\Filament\Resources\PaymentSettings\Pages\CreatePaymentSetting;
use App\Filament\Resources\PaymentSettings\Pages\EditPaymentSetting;
use App\Filament\Resources\PaymentSettings\Pages\ListPaymentSettings;
use App\Filament\Resources\PaymentSettings\Schemas\PaymentSettingForm;
use App\Filament\Resources\PaymentSettings\Tables\PaymentSettingsTable;
use App\Models\PaymentSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentSettingResource extends Resource
{
    protected static ?string $model = PaymentSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Payment Settings';

    public static function form(Schema $schema): Schema
    {
        return PaymentSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentSettings::route('/'),
            'create' => CreatePaymentSetting::route('/create'),
            'edit' => EditPaymentSetting::route('/{record}/edit'),
        ];
    }
}