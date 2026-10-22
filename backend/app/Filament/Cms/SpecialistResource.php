<?php

namespace App\Filament\Cms;

use App\Models\Specialist;

class SpecialistResource extends CmsResource
{
    protected static ?string $model = Specialist::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSpecialist::route('/')];
    }
}
