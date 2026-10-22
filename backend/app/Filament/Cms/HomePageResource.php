<?php

namespace App\Filament\Cms;

use App\Models\HomePage;

class HomePageResource extends CmsResource
{
    protected static ?string $model = HomePage::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageHomePage::route('/')];
    }
}
