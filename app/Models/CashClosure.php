<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosure extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'opening_balance',
        'income',
        'expense',
        'expected_balance',
        'real_balance',
        'difference',
        'observation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}