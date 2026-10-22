<?php

namespace App\Models;

class Testimonial extends CmsModel
{
    protected function casts(): array
    {
        return ['consent_granted' => 'boolean', 'consent_recorded_at' => 'date', 'consent_expires_at' => 'date'];
    }
}
