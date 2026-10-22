<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\TreatmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTreatment extends ManageRecords
{
    protected static string $resource = TreatmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
