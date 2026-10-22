<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Result;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CmsValidation
{
    public static function validate(Model $model): void
    {
        $definition = CmsDefinition::for($model::class);
        if ($definition['singleton'] ?? false) {
            $model->key = 'main';
            if ($model->newQuery()->where('key', 'main')->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))->exists()) {
                throw ValidationException::withMessages(['key' => 'Ya existe esta configuración. Edite el registro actual.']);
            }
        }
        foreach ($definition['fields'] as $field) {
            $value = $model->getAttribute($field['name']);
            if ($field['type'] === 'rich' && is_string($value)) {
                $model->setAttribute($field['name'], SafeHtml::clean($value));
            }
            if ($field['type'] === 'repeater' && is_array($value)) {
                foreach ($value as &$row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    foreach ($field['children'] as $child) {
                        if ($child['type'] === 'rich' && is_string($row[$child['name']] ?? null)) {
                            $row[$child['name']] = SafeHtml::clean($row[$child['name']]);
                        }
                    }
                }
                $model->setAttribute($field['name'], $value);
            }
        }
        $rules = self::rules($definition['fields'], $model);
        if ($model instanceof Result) {
            $rules['before_image'][0] = 'required';
            $rules['after_image'][0] = 'required';
        }
        Validator::make($model->attributesToArray(), $rules)->validate();
        if ($model instanceof Result || $model instanceof Testimonial) {
            if ($model->exists && $model->isDirty('consent_granted') && ! $model->consent_granted) {
                $model->status = 'draft';
            }
            if ($model->status === 'published') {
                $valid = $model->consent_granted && $model->consent_document && Storage::disk('cms_private')->exists($model->consent_document) && $model->consent_recorded_at && $model->consent_recorded_at->startOfDay()->lte(today()) && (! $model->consent_expires_at || $model->consent_expires_at->endOfDay()->gte(now()));
                if (! $valid) {
                    throw ValidationException::withMessages(['consent_document' => 'La publicación requiere consentimiento vigente, fecha y documento privado válido.']);
                }
                if ($model instanceof Result && (! $model->before_image || ! $model->after_image || ! $model->treatment()->where('status', 'published')->exists())) {
                    throw ValidationException::withMessages(['treatment_id' => 'Publique el tratamiento y cargue ambas imágenes antes de publicar.']);
                }
            }
        }
        if ($model instanceof Post) {
            if ($model->exists && $model->requires_medical_review && $model->isDirty(['content', 'title', 'excerpt']) && ! $model->isDirty('reviewed_at')) {
                $model->reviewed_at = null;
                $model->status = 'draft';
            }
            if ($model->status === 'published') {
                if (! $model->published_at) {
                    throw ValidationException::withMessages(['published_at' => 'Indique la fecha de publicación.']);
                }
                if ($model->requires_medical_review && (! $model->reviewed_at || $model->reviewed_at->isFuture() || ! $model->medicalReviewer()->where('status', 'published')->exists())) {
                    throw ValidationException::withMessages(['medical_reviewer_id' => 'Se necesita un especialista publicado y una revisión médica completada.']);
                }
            }
        }
    }

    private static function rules(array $fields, Model $model, string $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $name = $prefix.$field['name'];
            $type = $field['type'];
            $rule = [$field['required'] ?? false ? 'required' : 'nullable'];
            if (in_array($type, ['repeater', 'gallery', 'keyvalue'])) {
                $rule[] = 'array';
                $rule[] = 'max:'.($field['maxItems'] ?? 30);
            } elseif (in_array($type, ['number', 'treatment', 'reviewer'])) {
                $rule[] = 'integer';
                $rule[] = 'min:0';
                if ($type === 'number') {
                    $rule[] = 'max:1000000';
                }
            } elseif ($type === 'toggle') {
                $rule[] = 'boolean';
            } elseif (in_array($type, ['date', 'datetime'])) {
                $rule[] = 'date';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:'.($field['max'] ?? 255);
            }
            if ($type === 'slug') {
                $rule[] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
                $rule[] = Rule::unique($model->getTable(), $field['name'])->ignore($model->getKey());
            }
            if ($type === 'select') {
                $rule[] = Rule::in(array_merge(array_keys($field['options']), $field['name'] === 'status' ? ['archived'] : []));
            }
            if ($type === 'email') {
                $rule[] = 'email:rfc';
            }
            if ($type === 'phone') {
                $rule[] = 'regex:/^[+0-9() .-]{7,30}$/';
            }
            if ($type === 'link') {
                $rule[] = fn ($attribute, $value, $fail) => SafeHtml::url($value) ? null : $fail('Use un enlace seguro, sin credenciales.');
            }
            if ($type === 'treatment') {
                $rule[] = 'exists:treatments,id';
            }
            if ($type === 'reviewer') {
                $rule[] = 'exists:specialists,id';
            }
            if ($type === 'consent') {
                $rule[] = 'regex:~^consents/[A-Za-z0-9_-]+\.(pdf|jpg|jpeg|png)$~';
            }
            if ($type === 'patientImage') {
                $rule[] = 'uuid';
                $rule[] = 'exists:media_assets,id';
            }
            if ($type === 'image') {
                $rule[] = function ($attribute, $value, $fail) {
                    if (! Str::isUuid($value) && ! SafeHtml::url($value)) {
                        $fail('Seleccione una imagen válida.');
                    } if (Str::isUuid($value) && ! MediaAsset::whereKey($value)->exists()) {
                        $fail('La imagen no existe.');
                    }
                };
            }
            $rules[$name] = $rule;
            if ($type === 'repeater') {
                $rules[$name.'.*'] = ['array:'.implode(',', array_column($field['children'], 'name'))];
                $rules += self::rules($field['children'], $model, $name.'.*.');
                if (in_array('key', array_column($field['children'], 'name'))) {
                    $rules[$name.'.*.key'][] = 'distinct';
                }
            }
            if ($type === 'keyvalue') {
                $rules[$name.'.*'] = ['nullable', 'string', 'max:5000'];
            }
            if ($type === 'gallery') {
                $rules[$name.'.*'] = ['uuid', 'exists:media_assets,id'];
            }
        }

        return $rules;
    }
}
