<?php

namespace App\Filament\Resources\TrustStats\Pages;

use App\Filament\Resources\TrustStats\TrustStatResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrustStat extends EditRecord
{
    protected static string $resource = TrustStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
