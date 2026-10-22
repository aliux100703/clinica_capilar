<?php

namespace App\Filament\Cms;

use App\Models\Post;

class PostResource extends CmsResource
{
    protected static ?string $model = Post::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePost::route('/')];
    }
}
