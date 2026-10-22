<?php

namespace App\Models;

class HomePage extends CmsModel
{
    protected function casts(): array
    {
        return ['buttons' => 'array', 'sections' => 'array', 'copy' => 'array'];
    }
}
