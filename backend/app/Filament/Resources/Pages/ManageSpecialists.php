<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\SpecialistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecialists extends ManageRecords
{
    protected static string $resource = SpecialistResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
