<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = ['valuation_request_id', 'date', 'time', 'status', 'internal_notes'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function valuationRequest(): BelongsTo
    {
        return $this->belongsTo(ValuationRequest::class);
    }
}
