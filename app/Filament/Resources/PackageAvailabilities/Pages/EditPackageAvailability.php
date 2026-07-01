<?php

namespace App\Filament\Resources\PackageAvailabilities\Pages;

use App\Filament\Resources\PackageAvailabilities\PackageAvailabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPackageAvailability extends EditRecord
{
    protected static string $resource = PackageAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
