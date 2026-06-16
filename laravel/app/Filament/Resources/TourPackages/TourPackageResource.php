<?php

namespace App\Filament\Resources\TourPackages;

use App\Filament\Resources\TourPackages\Pages\CreateTourPackage;
use App\Filament\Resources\TourPackages\Pages\EditTourPackage;
use App\Filament\Resources\TourPackages\Pages\ListTourPackages;
use App\Filament\Resources\TourPackages\Pages\ViewTourPackage;
use App\Filament\Resources\TourPackages\Schemas\TourPackageForm;
use App\Filament\Resources\TourPackages\Schemas\TourPackageInfolist;
use App\Filament\Resources\TourPackages\Tables\TourPackagesTable;
use App\Models\TourPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TourPackageResource extends Resource
{
    protected static ?string $model = TourPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Products';

    public static function form(Schema $schema): Schema
    {
        return TourPackageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TourPackageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TourPackagesTable::configure($table);
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
            'index' => ListTourPackages::route('/'),
            'create' => CreateTourPackage::route('/create'),
            'view' => ViewTourPackage::route('/{record}'),
            'edit' => EditTourPackage::route('/{record}/edit'),
        ];
    }
}
