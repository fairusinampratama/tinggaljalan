<?php

namespace App\Filament\Resources\TrustStats\Pages;

use App\Filament\Resources\TrustStats\TrustStatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrustStats extends ListRecords
{
    protected static string $resource = TrustStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
