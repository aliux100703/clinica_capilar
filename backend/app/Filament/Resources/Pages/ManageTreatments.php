<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\TreatmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTreatments extends ManageRecords
{
    protected static string $resource = TreatmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
