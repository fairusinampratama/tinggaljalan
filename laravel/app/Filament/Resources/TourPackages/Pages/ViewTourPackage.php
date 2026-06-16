<?php

namespace App\Filament\Resources\TourPackages\Pages;

use App\Filament\Resources\TourPackages\TourPackageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTourPackage extends ViewRecord
{
    protected static string $resource = TourPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
