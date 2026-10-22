<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends CmsModel
{
    protected function casts(): array
    {
        return ['consent_granted' => 'boolean', 'consent_recorded_at' => 'date', 'consent_expires_at' => 'date'];
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
