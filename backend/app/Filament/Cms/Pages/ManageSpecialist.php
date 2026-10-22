<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\SpecialistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecialist extends ManageRecords
{
    protected static string $resource = SpecialistResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
