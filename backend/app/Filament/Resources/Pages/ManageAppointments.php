<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAppointments extends ManageRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
