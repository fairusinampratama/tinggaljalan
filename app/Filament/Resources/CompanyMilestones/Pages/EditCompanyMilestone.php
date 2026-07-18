<?php

namespace App\Filament\Resources\CompanyMilestones\Pages;

use App\Filament\Resources\CompanyMilestones\CompanyMilestoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyMilestone extends EditRecord
{
    protected static string $resource = CompanyMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
