<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

// Loaded by the auth provider only when Filament is installed.
class AdminUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->role === 'admin';
    }
}
