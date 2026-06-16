<?php

namespace App\Filament\Resources\AddOns\Pages;

use App\Filament\Resources\AddOns\AddOnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAddOn extends ViewRecord
{
    protected static string $resource = AddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
