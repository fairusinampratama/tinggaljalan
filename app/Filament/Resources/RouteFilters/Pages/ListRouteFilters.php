<?php

namespace App\Filament\Resources\RouteFilters\Pages;

use App\Filament\Resources\RouteFilters\RouteFilterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRouteFilters extends ListRecords
{
    protected static string $resource = RouteFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
