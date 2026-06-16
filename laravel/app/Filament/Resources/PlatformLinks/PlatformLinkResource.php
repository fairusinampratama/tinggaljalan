<?php

namespace App\Filament\Resources\PlatformLinks;

use App\Filament\Resources\PlatformLinks\Pages\CreatePlatformLink;
use App\Filament\Resources\PlatformLinks\Pages\EditPlatformLink;
use App\Filament\Resources\PlatformLinks\Pages\ListPlatformLinks;
use App\Filament\Resources\PlatformLinks\Pages\ViewPlatformLink;
use App\Filament\Resources\PlatformLinks\Schemas\PlatformLinkForm;
use App\Filament\Resources\PlatformLinks\Schemas\PlatformLinkInfolist;
use App\Filament\Resources\PlatformLinks\Tables\PlatformLinksTable;
use App\Models\PlatformLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlatformLinkResource extends Resource
{
    protected static ?string $model = PlatformLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';

    public static function form(Schema $schema): Schema
    {
        return PlatformLinkForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlatformLinkInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformLinksTable::configure($table);
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
            'index' => ListPlatformLinks::route('/'),
            'create' => CreatePlatformLink::route('/create'),
            'view' => ViewPlatformLink::route('/{record}'),
            'edit' => EditPlatformLink::route('/{record}/edit'),
        ];
    }
}
