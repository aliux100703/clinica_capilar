<?php

namespace App\Models;

use App\Models\Concerns\HasPublicContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use HasPublicContent;

    protected $fillable = ['name', 'slug', 'description', 'content', 'image', 'status', 'gallery', 'sort_order', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return ['gallery' => 'array'];
    }

    public function valuationRequests(): HasMany
    {
        return $this->hasMany(ValuationRequest::class);
    }
}
