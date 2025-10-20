<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_type',
        'subtotal',
        'discount',
        'total_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con los detalles de la venta
     */
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }
    public function financialMovement()
    {
        return $this->hasOne(\App\Models\FinancialMovement::class);
    }
    /**
     * Calcular el total de items vendidos
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->saleDetails->sum('quantity');
    }

    /**
     * Obtener el total sin descuento
     */
    public function getSubtotalCalculatedAttribute(): float
    {
        return $this->saleDetails->sum(function ($detail) {
            return $detail->quantity * $detail->unit_price;
        });
    }

    /**
     * Scope para ventas de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para ventas del mes
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Scope para ventas pagadas
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope para ventas pendientes
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            FinancialMovement::class,
            'sale_id',      // Foreign key en financial_movements
            'id',           // Foreign key en users
            'id',           // Local key en sales
            'user_id'       // Local key en financial_movements
        );
    }

}