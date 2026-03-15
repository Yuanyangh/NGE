<?php

namespace App\Filament\Resources\CompensationPlanResource\Pages;

use App\Filament\Resources\CompensationPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompensationPlan extends EditRecord
{
    protected static string $resource = CompensationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
