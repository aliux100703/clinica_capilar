<?php

namespace App\Models;

use App\Models\Concerns\HasPublicContent;
use Illuminate\Database\Eloquent\Model;

abstract class CmsModel extends Model
{
    protected $attributes = ['status' => 'draft'];

    use HasPublicContent;

    protected $guarded = ['id'];
}
