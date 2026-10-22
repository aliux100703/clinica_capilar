<?php

namespace App\Models\Concerns;

use App\Models\Post;
use App\Models\Result;
use App\Models\Testimonial;
use App\Observers\ContentObserver;
use App\Support\CmsValidation;
use Illuminate\Database\Eloquent\Builder;

trait HasPublicContent
{
    public static function bootHasPublicContent(): void
    {
        static::saving(fn ($model) => CmsValidation::validate($model));
        static::observe(ContentObserver::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        $query->where('status', 'published');
        if ($this instanceof Post) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn ($q) => $q->where('requires_medical_review', false)->orWhere(fn ($review) => $review->whereNotNull('reviewed_at')->where('reviewed_at', '<=', now())->whereHas('medicalReviewer', fn ($s) => $s->where('status', 'published'))));
        }
        if ($this instanceof Result || $this instanceof Testimonial) {
            $query->where('consent_granted', true)->whereNotNull('consent_document')->whereNotNull('consent_recorded_at')->whereDate('consent_recorded_at', '<=', today())->where(fn ($q) => $q->whereNull('consent_expires_at')->orWhereDate('consent_expires_at', '>=', today()));
            if ($this instanceof Result) {
                $query->whereHas('treatment', fn ($q) => $q->where('status', 'published'));
            }
        }

        return $query;
    }
}
