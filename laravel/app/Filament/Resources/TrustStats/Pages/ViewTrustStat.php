<?php

namespace App\Filament\Resources\TrustStats\Pages;

use App\Filament\Resources\TrustStats\TrustStatResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrustStat extends ViewRecord
{
    protected static string $resource = TrustStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
