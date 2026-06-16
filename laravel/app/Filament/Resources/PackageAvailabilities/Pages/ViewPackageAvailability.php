<?php

namespace App\Filament\Resources\PackageAvailabilities\Pages;

use App\Filament\Resources\PackageAvailabilities\PackageAvailabilityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPackageAvailability extends ViewRecord
{
    protected static string $resource = PackageAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
