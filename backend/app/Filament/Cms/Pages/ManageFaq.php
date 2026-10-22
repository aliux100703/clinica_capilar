<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\FaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaq extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
