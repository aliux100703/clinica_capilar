<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValuationRequest extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'treatment_id', 'preferred_date', 'preferred_time', 'consent', 'consented_at', 'status'];

    protected function casts(): array
    {
        return ['consent' => 'boolean', 'consented_at' => 'datetime', 'preferred_date' => 'date'];
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
