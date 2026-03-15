<?php

namespace App\Filament\Resources\CompensationPlanResource\Pages;

use App\Filament\Resources\CompensationPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompensationPlans extends ListRecords
{
    protected static string $resource = CompensationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
