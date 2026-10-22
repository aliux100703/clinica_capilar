<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\Result;
use App\Models\Testimonial;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\Storage;

class CmsMediaController extends Controller
{
    public function publicImage(MediaAsset $asset)
    {
        abort_unless(PublicMedia::visible($asset->id), 404);

        return $this->image($asset);
    }

    public function image(MediaAsset $asset)
    {
        abort_unless(Storage::disk('cms_media')->exists($asset->path), 404);

        return response()->file(Storage::disk('cms_media')->path($asset->path), ['Content-Type' => $asset->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'])->setPrivate();
    }

    public function consent(string $type, int $record)
    {
        $class = match ($type) {
            'results' => Result::class,'testimonials' => Testimonial::class,default => null
        };
        abort_unless($class, 404);
        $item = $class::findOrFail($record);
        abort_unless($item->consent_document && Storage::disk('cms_private')->exists($item->consent_document), 404);

        return response()->download(Storage::disk('cms_private')->path($item->consent_document), 'consentimiento-privado.'.pathinfo($item->consent_document, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->setPrivate();
    }
}
