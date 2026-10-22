<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends CmsModel
{
    protected $attributes = ['status' => 'draft', 'requires_medical_review' => true];

    protected function casts(): array
    {
        return ['requires_medical_review' => 'boolean', 'published_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function medicalReviewer(): BelongsTo
    {
        return $this->belongsTo(Specialist::class, 'medical_reviewer_id');
    }
}
