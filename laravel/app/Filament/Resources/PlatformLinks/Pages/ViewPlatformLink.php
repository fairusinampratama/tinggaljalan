<?php

namespace App\Filament\Resources\PlatformLinks\Pages;

use App\Filament\Resources\PlatformLinks\PlatformLinkResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformLink extends ViewRecord
{
    protected static string $resource = PlatformLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
