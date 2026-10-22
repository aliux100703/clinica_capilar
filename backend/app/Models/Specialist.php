<?php

namespace App\Models;

use App\Models\Concerns\HasPublicContent;
use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    use HasPublicContent;

    protected $fillable = ['name', 'specialty', 'description', 'photo', 'credentials', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['credentials' => 'array'];
    }
}
