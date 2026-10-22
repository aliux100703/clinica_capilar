<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'message', 'consent', 'consented_at', 'status'];

    protected function casts(): array
    {
        return ['consent' => 'boolean', 'consented_at' => 'datetime'];
    }
}
