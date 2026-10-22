<?php

namespace App\Filament\Cms;

use App\Models\MediaAsset;
use App\Services\StoreCmsImage;
use App\Support\SafeHtml;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CmsFields
{
    public static function build(array $definitions, string $model): array
    {
        $fields = [];
        $directory = match (class_basename($model)) {
            'HomePage' => 'home','SiteSetting' => 'settings','Treatment' => 'treatments','Specialist' => 'specialists','Result' => 'results','Post' => 'posts','Testimonial' => 'testimonials',default => 'settings'
        };
        foreach ($definitions as $definition) {
            $name = $definition['name'];
            $type = $definition['type'];
            $field = match ($type) {
                'textarea' => Textarea::make($name)->rows(4),
                'rich' => RichEditor::make($name)->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo'])->dehydrateStateUsing(fn ($state) => SafeHtml::clean($state)),
                'select' => Select::make($name)->options($definition['options']),
                'number' => TextInput::make($name)->numeric()->minValue(0)->maxValue(1000000),
                'toggle' => Toggle::make($name),
                'date' => DatePicker::make($name),
                'datetime' => DateTimePicker::make($name)->seconds(false),
                'keyvalue' => KeyValue::make($name)->keyLabel('Clave de texto')->valueLabel('Texto público'),
                'repeater' => Repeater::make($name)->schema(self::build($definition['children'], $model))->maxItems($definition['maxItems'])->defaultItems(0)->collapsible(),
                'treatment' => Select::make($name)->relationship('treatment', 'name')->searchable()->preload(),
                'reviewer' => Select::make($name)->relationship('medicalReviewer', 'name', modifyQueryUsing: fn ($query) => $query->where('status', 'published'))->searchable()->preload(),
                'image','patientImage','gallery' => self::image($name, $directory, $type === 'gallery'),
                'consent' => FileUpload::make($name)->disk('cms_private')->directory('consents')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(5120)->previewable(false)->downloadable(false)->openable(false)->deleteUploadedFileUsing(fn () => null),
                default => TextInput::make($name)
            };
            $field->label($definition['label']);
            if ($name === 'status') {
                $field->helperText('Borrador guarda los cambios sin mostrarlos en la web. Selecciona Publicado y guarda para hacerlos visibles.');
            }
            if ($definition['required'] ?? false) {
                $field->required();
            }
            if (class_basename($model) === 'Result' && in_array($name, ['before_image', 'after_image'])) {
                $field->required();
            }
            if (array_key_exists('default', $definition)) {
                $field->default($definition['default']);
            }
            if (($definition['max'] ?? null) && in_array($type, ['text', 'textarea', 'slug', 'phone', 'email', 'link', 'rich'])) {
                $field->maxLength($definition['max']);
            }
            if ($type === 'email') {
                $field->email();
            }
            if ($type === 'slug') {
                $field->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true);
            }
            if ($type === 'link') {
                $field->rules([fn () => function ($attribute, $value, $fail) {
                    if (! SafeHtml::url($value)) {
                        $fail('Use un enlace seguro.');
                    }
                }]);
            }
            if ($type === 'rich' || $type === 'repeater' || $type === 'keyvalue') {
                $field->columnSpanFull();
            }
            $fields[] = $field;
        }

        return $fields;
    }

    private static function image(string $name, string $directory, bool $multiple): FileUpload
    {
        // State contains asset UUIDs, not disk paths; resolve previews through MediaAsset.
        $field = FileUpload::make($name)->disk('cms_media')->visibility('private')->fetchFileInformation(false)->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => app(StoreCmsImage::class)->store($file, $directory))
            ->getUploadedFileUsing(function (string $file): ?array {
                if (! Str::isUuid($file)) {
                    return SafeHtml::url($file) ? ['name' => 'Imagen existente', 'size' => 0, 'type' => 'image/jpeg', 'url' => $file] : null;
                }
                $asset = MediaAsset::find($file);

                return $asset ? ['name' => 'Imagen optimizada.webp', 'size' => $asset->size, 'type' => $asset->mime, 'url' => url('/admin/media/'.$asset->id)] : null;
            })->deleteUploadedFileUsing(fn () => null)->helperText('JPEG, PNG o WebP; máximo 5 MB y 12 MP. Se optimiza a WebP sin metadatos. Retirar una imagen no borra archivos compartidos.');

        return $multiple ? $field->multiple()->maxFiles(20)->reorderable() : $field;
    }
}
