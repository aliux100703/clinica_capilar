<?php

namespace App\Filament\Cms;

use App\Models\Treatment;

class TreatmentResource extends CmsResource
{
    protected static ?string $model = Treatment::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTreatment::route('/')];
    }
}
