<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    protected $fillable = [
        'date',
        'initial_balance',
        'final_balance',
        'notes',
        'user_id',
    ];
    protected $casts = [
        'date' => 'date',
        'initial_balance' => 'decimal:2',
        'final_balance' => 'decimal:2',
    ];


    public function cashClosure()
    {
        return $this->hasOne(CashClosure::class, 'user_id', 'user_id')
                    ->whereColumn('date', 'cash_flows.date');
    }
    
    public function movements()
    {
        return $this->hasMany(FinancialMovement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recalculateFinalBalance(): void
    {
        $incomes = $this->movements()->where('type', FinancialMovement::TYPE_INCOME)->sum('amount');
        $expenses = $this->movements()->where('type', FinancialMovement::TYPE_EXPENSE)->sum('amount');

        $this->final_balance = $this->initial_balance + $incomes - $expenses;
        $this->save();
    }
    public function financialMovements()
    {
        return $this->hasMany(\App\Models\FinancialMovement::class);
    }
    public function generateClosure($realBalance = null, $observation = null): ?CashClosure
    {
        // Evitar cierres duplicados
        if (CashClosure::where('user_id', $this->user_id)->where('date', $this->date)->exists()) {
            return null;
        }

        $income = $this->financialMovements()->where('type', 'income')->sum('amount');
        $expense = $this->financialMovements()->where('type', 'expense')->sum('amount');

        $expected = $this->initial_balance + $income - $expense;
        $real = $realBalance ?? $expected;

        return CashClosure::create([
            'user_id' => $this->user_id,
            'date' => $this->date,
            'opening_balance' => $this->initial_balance,
            'income' => $income,
            'expense' => $expense,
            'expected_balance' => $expected,
            'real_balance' => $real,
            'difference' => $real - $expected,
            'observation' => $observation ?? 'Cierre automático',
        ]);
    }
    public static function getOrCreateToday($userId, $date)
    {
        $previous = self::where('user_id', $userId)
            ->where('date', '<', $date)
            ->orderByDesc('date')
            ->first();

        $initial = $previous?->final_balance ?? 0;

        return self::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['initial_balance' => $initial, 'final_balance' => $initial]
        );
    }

}
