<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'email',
            'phone',
            'nit',         
            'address',       
            'logo',   
            'status',
            'suspended_at',
            'suspension_reason',
            'max_users',
        ];
    }

    protected $fillable = [
        'id',
        'name',
        'slug',
        'email',
        'phone',
        'nit',         
        'address',       
        'logo',   
        'status',
        'suspended_at',
        'suspension_reason',
        'max_users',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'suspended_at' => 'datetime',
    ];

    // AGREGAR ESTE MÉTODO
    public function getDatabaseName(): string
    {
        return config('tenancy.database.prefix') . $this->id . config('tenancy.database.suffix');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    public function getUsersCount(): int
    {
        if (!tenancy()->initialized) {
            return 0;
        }
        
        try {
            return \App\Models\User::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function canAddUser(): bool
    {
        return $this->getUsersCount() < $this->max_users;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo 
            ? asset('storage/' . $this->logo)
            : null;
    }

    public function hasLogo(): bool
    {
        return !empty($this->logo);
    }
}