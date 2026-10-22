<?php

namespace App\Filament\Cms;

use App\Models\Faq;

class FaqResource extends CmsResource
{
    protected static ?string $model = Faq::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFaq::route('/')];
    }
}
