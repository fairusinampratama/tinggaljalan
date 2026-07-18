<?php

namespace App\Filament\Resources\CompanyMilestones\Pages;

use App\Filament\Resources\CompanyMilestones\CompanyMilestoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyMilestones extends ListRecords
{
    protected static string $resource = CompanyMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
