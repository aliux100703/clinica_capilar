<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ValuationRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageValuationRequests extends ManageRecords
{
    protected static string $resource = ValuationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
