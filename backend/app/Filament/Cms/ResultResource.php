<?php

namespace App\Filament\Cms;

use App\Models\Result;

class ResultResource extends CmsResource
{
    protected static ?string $model = Result::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageResult::route('/')];
    }
}
