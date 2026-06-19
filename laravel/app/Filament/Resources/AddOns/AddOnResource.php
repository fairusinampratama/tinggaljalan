<?php

namespace App\Filament\Resources\AddOns;

use App\Filament\Resources\AddOns\Pages\CreateAddOn;
use App\Filament\Resources\AddOns\Pages\EditAddOn;
use App\Filament\Resources\AddOns\Pages\ListAddOns;
use App\Filament\Resources\AddOns\Pages\ViewAddOn;
use App\Filament\Resources\AddOns\Schemas\AddOnForm;
use App\Filament\Resources\AddOns\Schemas\AddOnInfolist;
use App\Filament\Resources\AddOns\Tables\AddOnsTable;
use App\Models\AddOn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AddOnResource extends Resource
{
    protected static ?string $model = AddOn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Products';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return AddOnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AddOnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddOnsTable::configure($table);
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
            'index' => ListAddOns::route('/'),
            'create' => CreateAddOn::route('/create'),
            'view' => ViewAddOn::route('/{record}'),
            'edit' => EditAddOn::route('/{record}/edit'),
        ];
    }
}
