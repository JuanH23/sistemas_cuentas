<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialMovement extends Model
{
    use SoftDeletes;

    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'date',
        'category',
        'description',
        'amount',
        'type',
        'method',         // ← método de pago adicional (opcional)
        'cash_flow_id',
        'user_id',
        'sale_id',
    ];

    public static function getTypes(): array
    {
        return [
            self::TYPE_INCOME => 'Ingreso',
            self::TYPE_EXPENSE => 'Egreso',
        ];
    }

    public function cashFlow()
    {
        return $this->belongsTo(CashFlow::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    public function platformMovement()
    {
        return $this->belongsTo(\App\Models\PlatformMovement::class);
    }
}
