<?php

namespace App\Filament\Resources\CompanyMilestones\Pages;

use App\Filament\Resources\CompanyMilestones\CompanyMilestoneResource;
use App\Models\CompanyMilestone;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyMilestone extends CreateRecord
{
    protected static string $resource = CompanyMilestoneResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort_order'] ??= ((int) CompanyMilestone::query()->max('sort_order')) + 1;

        return $data;
    }
}
