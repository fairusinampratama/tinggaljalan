<?php

namespace App\Filament\Resources\RouteFilters\Pages;

use App\Filament\Resources\RouteFilters\RouteFilterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRouteFilter extends CreateRecord
{
    protected static string $resource = RouteFilterResource::class;
}
