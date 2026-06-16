<?php

namespace App\Filament\Resources\TrustStats;

use App\Filament\Resources\TrustStats\Pages\CreateTrustStat;
use App\Filament\Resources\TrustStats\Pages\EditTrustStat;
use App\Filament\Resources\TrustStats\Pages\ListTrustStats;
use App\Filament\Resources\TrustStats\Pages\ViewTrustStat;
use App\Filament\Resources\TrustStats\Schemas\TrustStatForm;
use App\Filament\Resources\TrustStats\Schemas\TrustStatInfolist;
use App\Filament\Resources\TrustStats\Tables\TrustStatsTable;
use App\Models\TrustStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrustStatResource extends Resource
{
    protected static ?string $model = TrustStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Management';

    public static function form(Schema $schema): Schema
    {
        return TrustStatForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrustStatInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrustStatsTable::configure($table);
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
            'index' => ListTrustStats::route('/'),
            'create' => CreateTrustStat::route('/create'),
            'view' => ViewTrustStat::route('/{record}'),
            'edit' => EditTrustStat::route('/{record}/edit'),
        ];
    }
}
