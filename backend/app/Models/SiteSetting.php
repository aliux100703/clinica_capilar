<?php

namespace App\Models;

class SiteSetting extends CmsModel
{
    protected function casts(): array
    {
        return ['social_links' => 'array', 'opening_hours' => 'array', 'navigation' => 'array', 'pages' => 'array', 'interface_copy' => 'array'];
    }
}
