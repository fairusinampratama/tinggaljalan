<?php

namespace App\Filament\Resources\PlatformLinks\Pages;

use App\Filament\Resources\PlatformLinks\PlatformLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformLinks extends ListRecords
{
    protected static string $resource = PlatformLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
