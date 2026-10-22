<?php

namespace App\Filament\Cms;

use App\Models\Testimonial;

class TestimonialResource extends CmsResource
{
    protected static ?string $model = Testimonial::class;

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTestimonial::route('/')];
    }
}
