<?php

namespace App\Filament\Resources\RouteFilters\Pages;

use App\Filament\Resources\RouteFilters\RouteFilterResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRouteFilter extends ViewRecord
{
    protected static string $resource = RouteFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
