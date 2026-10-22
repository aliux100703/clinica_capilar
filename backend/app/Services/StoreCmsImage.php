<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreCmsImage
{
    public function store(UploadedFile $file, string $directory): string
    {
        if (! in_array($directory, ['home', 'settings', 'treatments', 'specialists', 'results', 'posts', 'testimonials'], true)) {
            throw ValidationException::withMessages(['image' => 'Destino de imagen no válido.']);
        }
        validator(['image' => $file], ['image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120']])->validate();
        $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] * $size[1] > 12000000 || max($size[0], $size[1]) > 6000) {
            throw ValidationException::withMessages(['image' => 'La imagen debe ser válida y no superar 12 megapíxeles ni 6000 píxeles por lado.']);
        }
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw ValidationException::withMessages(['image' => 'Active la extensión GD de PHP con soporte WebP para cargar imágenes.']);
        }
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $source) {
            throw ValidationException::withMessages(['image' => 'No se pudo procesar la imagen.']);
        }
        $target = null;
        try {
            if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
                $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
                if (in_array($orientation, [2, 4, 5, 7])) {
                    imageflip($source, IMG_FLIP_HORIZONTAL);
                }
                $degrees = match ($orientation) {
                    3,4 => 180,5,6 => -90,7,8 => 90,default => 0
                };
                if ($degrees) {
                    $rotated = imagerotate($source, $degrees, 0);
                    imagedestroy($source);
                    $source = $rotated;
                }
            }
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, 1920 / max($width, $height));
            $w = max(1, (int) round($width * $scale));
            $h = max(1, (int) round($height * $scale));
            $target = imagecreatetruecolor($w, $h);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $w, $h, $width, $height);
            ob_start();
            $written = imagewebp($target, null, 82);
            $bytes = ob_get_clean();
            if (! $written || ! $bytes) {
                throw ValidationException::withMessages(['image' => 'No se pudo optimizar la imagen.']);
            }
            $id = (string) Str::uuid();
            $path = $directory.'/'.$id.'.webp';
            Storage::disk('cms_media')->put($path, $bytes);
            $asset = new MediaAsset;
            $asset->id = $id;
            $asset->fill(['path' => $path, 'mime' => 'image/webp', 'width' => $w, 'height' => $h, 'size' => strlen($bytes)]);
            $asset->save();

            return $id;
        } finally {
            imagedestroy($source);
            if ($target) {
                imagedestroy($target);
            }
        }
    }
}
