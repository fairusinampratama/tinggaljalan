<?php

namespace App\Filament\Resources\PackageAvailabilities;

use App\Filament\Resources\PackageAvailabilities\Pages\CreatePackageAvailability;
use App\Filament\Resources\PackageAvailabilities\Pages\EditPackageAvailability;
use App\Filament\Resources\PackageAvailabilities\Pages\ListPackageAvailabilities;
use App\Filament\Resources\PackageAvailabilities\Pages\ViewPackageAvailability;
use App\Filament\Resources\PackageAvailabilities\Schemas\PackageAvailabilityForm;
use App\Filament\Resources\PackageAvailabilities\Schemas\PackageAvailabilityInfolist;
use App\Filament\Resources\PackageAvailabilities\Tables\PackageAvailabilitiesTable;
use App\Models\PackageAvailability;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PackageAvailabilityResource extends Resource
{
    protected static ?string $model = PackageAvailability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Products';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return PackageAvailabilityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PackageAvailabilityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackageAvailabilitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackageAvailabilities::route('/'),
            'create' => CreatePackageAvailability::route('/create'),
            'view' => ViewPackageAvailability::route('/{record}'),
            'edit' => EditPackageAvailability::route('/{record}/edit'),
        ];
    }
}
