<?php

namespace App\Filament\Resources\RouteFilters;

use App\Filament\Resources\RouteFilters\Pages\CreateRouteFilter;
use App\Filament\Resources\RouteFilters\Pages\EditRouteFilter;
use App\Filament\Resources\RouteFilters\Pages\ListRouteFilters;
use App\Filament\Resources\RouteFilters\Pages\ViewRouteFilter;
use App\Filament\Resources\RouteFilters\Schemas\RouteFilterForm;
use App\Filament\Resources\RouteFilters\Schemas\RouteFilterInfolist;
use App\Filament\Resources\RouteFilters\Tables\RouteFiltersTable;
use App\Models\RouteFilter;
use App\Support\RouteFilterOptions;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RouteFilterResource extends Resource
{
    protected static ?string $model = RouteFilter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Products';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Route Filters';

    public static function form(Schema $schema): Schema
    {
        self::ensureRouteFiltersTable();

        return RouteFilterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        self::ensureRouteFiltersTable();

        return RouteFilterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        self::ensureRouteFiltersTable();

        return RouteFiltersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        self::ensureRouteFiltersTable();

        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRouteFilters::route('/'),
            'create' => CreateRouteFilter::route('/create'),
            'view' => ViewRouteFilter::route('/{record}'),
            'edit' => EditRouteFilter::route('/{record}/edit'),
        ];
    }

    private static function ensureRouteFiltersTable(): void
    {
        try {
            if (DatabaseSchema::hasTable('route_filters')) {
                return;
            }

            DatabaseSchema::create('route_filters', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->json('label');
                $table->json('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            RouteFilterOptions::seedDefaults();
        } catch (QueryException) {
            //
        }
    }
}
