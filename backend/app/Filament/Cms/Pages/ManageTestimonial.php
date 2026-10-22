<?php

namespace App\Filament\Cms\Pages;

use App\Filament\Cms\TestimonialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTestimonial extends ManageRecords
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear contenido')];
    }
}
