<?php

namespace App\Filament\Resources\PackageAvailabilities\Pages;

use App\Filament\Resources\PackageAvailabilities\PackageAvailabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackageAvailabilities extends ListRecords
{
    protected static string $resource = PackageAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
