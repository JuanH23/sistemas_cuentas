<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;
    protected $hidden = [
        'name',
        'email',
        'password',
        'is_super_admin', 
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];
    public function canAccessPanel(Panel $panel): bool
    {
        // Panel Admin Central: solo super admins
        if ($panel->getId() === 'admin' && !tenancy()->initialized) {
            return $this->is_super_admin ?? false;
        }

        // Panel Tenant: usuarios normales en su tenant
        if ($panel->getId() === 'app' && tenancy()->initialized) {
            return true; // Ya están en su tenant
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin ?? false;
    }
}
