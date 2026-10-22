<?php

namespace App\Support;

use App\Models\HomePage;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Result;
use App\Models\SiteSetting;
use App\Models\Specialist;
use App\Models\Testimonial;
use App\Models\Treatment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicMedia
{
    public static function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (Str::isUuid($value)) {
            return url('/api/v1/media/'.$value);
        }
        $url = SafeHtml::url($value);

        return $url && (str_starts_with($url, '/') || in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) ? $url : null;
    }

    public static function consentValid($record): bool
    {
        return $record->consent_granted && $record->consent_document && Storage::disk('cms_private')->exists($record->consent_document);
    }

    public static function visible(string $id): bool
    {
        $asset = MediaAsset::find($id);
        $restricted = $asset && Str::startsWith($asset->path, ['results/', 'testimonials/']);
        if (! $restricted && (HomePage::published()->where('cover_image', $id)->exists() || SiteSetting::published()->where('logo', $id)->exists() || Specialist::published()->where('photo', $id)->exists() || Post::published()->where('image', $id)->exists())) {
            return true;
        }
        if (! $restricted && Treatment::published()->where(fn ($q) => $q->where('image', $id)->orWhereJsonContains('gallery', $id))->exists()) {
            return true;
        }
        foreach (Result::published()->where(fn ($q) => $q->where('before_image', $id)->orWhere('after_image', $id))->get() as $record) {
            if (self::consentValid($record)) {
                return true;
            }
        }
        foreach (Testimonial::published()->where('photo', $id)->get() as $record) {
            if (self::consentValid($record)) {
                return true;
            }
        }

        return false;
    }
}
