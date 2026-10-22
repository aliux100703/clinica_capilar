<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\ResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageResult extends ManageRecords
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
