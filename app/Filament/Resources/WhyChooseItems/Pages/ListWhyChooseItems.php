<?php

namespace App\Filament\Resources\WhyChooseItems\Pages;

use App\Filament\Resources\WhyChooseItems\WhyChooseItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhyChooseItems extends ListRecords
{
    protected static string $resource = WhyChooseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
