<?php

namespace App\Filament\Resources\PlatformLinks\Pages;

use App\Filament\Resources\PlatformLinks\PlatformLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformLink extends EditRecord
{
    protected static string $resource = PlatformLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
