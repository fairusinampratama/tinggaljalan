<?php

namespace App\Filament\Resources\RouteFilters\Pages;

use App\Filament\Resources\RouteFilters\RouteFilterResource;
use Filament\Resources\Pages\EditRecord;

class EditRouteFilter extends EditRecord
{
    protected static string $resource = RouteFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ];
    }
}
