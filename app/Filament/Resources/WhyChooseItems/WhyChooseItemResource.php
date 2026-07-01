<?php

namespace App\Filament\Resources\WhyChooseItems;

use App\Filament\Resources\WhyChooseItems\Pages;
use App\Filament\Resources\WhyChooseItems\Schemas\WhyChooseItemForm;
use App\Filament\Resources\WhyChooseItems\Tables\WhyChooseItemsTable;
use App\Models\WhyChooseItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhyChooseItemResource extends Resource
{
    protected static ?string $model = WhyChooseItem::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;
    protected static string|\UnitEnum|null $navigationGroup = 'Content';
    protected static ?int $navigationSort = 50;
    protected static ?string $navigationLabel = 'Why Choose Us';
    protected static ?string $modelLabel = 'Why Choose Item';
    protected static ?string $pluralModelLabel = 'Why Choose Items';

    public static function form(Schema $schema): Schema
    {
        return WhyChooseItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhyChooseItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhyChooseItems::route('/'),
            'create' => Pages\CreateWhyChooseItem::route('/create'),
            'edit' => Pages\EditWhyChooseItem::route('/{record}/edit'),
        ];
    }
}
