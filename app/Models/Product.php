<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Product extends Model
{
    use Notifiable;
    use HasRoles;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'price',
        'unit_price',
        'is_initial_inventory',
        'acquisition_date',
    ];

    protected $casts = [
        // ... casts existentes
        'is_initial_inventory' => 'boolean',
        'acquisition_date' => 'date',
    ];

    public function financialMovement()
    {
        return $this->belongsTo(\App\Models\FinancialMovement::class);
    }
    
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
