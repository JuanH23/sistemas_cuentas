<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformMovement extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'date',
        'platform',
        'type',
        'reference',
        'operator',
        'amount',
        'description',
        'user_id',
        'cash_flow_id',
    ];
    protected static function booted(): void
    {
        static::creating(function ($movement) {
            if (empty($movement->date)) {
                $movement->date = now();
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashFlow()
    {
        return $this->belongsTo(CashFlow::class);
    }
    public function platform()
    {
        return $this->belongsTo(\App\Models\Platform::class);
    }

    public function platformMovementType()
    {
        return $this->belongsTo(\App\Models\PlatformMovementType::class);
    }
}