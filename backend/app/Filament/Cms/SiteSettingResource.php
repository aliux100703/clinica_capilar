<?php

namespace App\Filament\Cms;

use App\Models\SiteSetting;

class SiteSettingResource extends CmsResource
{
    protected static ?string $model = SiteSetting::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSiteSetting::route('/')];
    }
}
