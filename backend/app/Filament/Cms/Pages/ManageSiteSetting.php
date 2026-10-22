<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\SiteSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSiteSetting extends ManageRecords
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
