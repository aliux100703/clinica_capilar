<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CmsPublicData
{
    public static function make(Model $model): array
    {
        $fields = match (class_basename($model)) {
            'Treatment' => ['id', 'name', 'slug', 'description', 'content', 'image', 'gallery', 'seo_title', 'seo_description'],
            'Specialist' => ['id', 'name', 'specialty', 'description', 'photo', 'credentials'],
            'HomePage' => ['title', 'subtitle', 'cover_image', 'image_alt', 'buttons', 'presentation', 'sections', 'copy', 'seo_title', 'seo_description'],
            'SiteSetting' => ['trade_name', 'tagline', 'logo', 'phone', 'whatsapp', 'email', 'address', 'social_links', 'opening_hours', 'contact_information', 'navigation', 'footer_text', 'pages', 'interface_copy', 'seo_title', 'seo_description'],
            'Result' => ['id', 'treatment_id', 'before_image', 'after_image', 'description', 'evolution_time'],
            'Post' => ['id', 'title', 'slug', 'excerpt', 'content', 'image', 'category', 'author', 'published_at', 'seo_title', 'seo_description'],
            'Testimonial' => ['id', 'public_name', 'quote', 'photo'],
            'Faq' => ['id', 'question', 'answer', 'category'],
        };
        $data = $model->only($fields);
        foreach (['image', 'photo', 'cover_image', 'logo', 'before_image', 'after_image'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = PublicMedia::url($data[$field]);
            }
        }
        if (isset($data['gallery'])) {
            $data['gallery'] = array_values(array_filter(array_map(fn ($id) => PublicMedia::url($id), $data['gallery'])));
        }
        if (array_key_exists('content', $data)) {
            $data['content'] = SafeHtml::clean($data['content']);
        }
        if (isset($data['pages'])) {
            $data['pages'] = array_map(function ($page) {
                $page['content'] = SafeHtml::clean($page['content'] ?? null);

                return $page;
            }, $data['pages']);
        }
        if (array_key_exists('credentials', $data)) {
            $data['credentials'] = collect($data['credentials'] ?? [])->filter(fn ($c) => is_array($c) && ($c['verified'] ?? false) === true)->map(fn ($c) => Arr::only($c, ['title', 'issuer', 'registration', 'verification_url']))->values()->all();
        }
        if ($model instanceof Post) {
            $data['medical_reviewer'] = $model->reviewed_at && ! $model->reviewed_at->isFuture() && $model->medicalReviewer && $model->medicalReviewer->status === 'published' ? $model->medicalReviewer->only(['name', 'specialty']) : null;
        }

        return $data;
    }
}
