<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\HomePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHomePage extends ManageRecords
{
    protected static string $resource = HomePageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
