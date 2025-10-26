<?php
// app/Filament/App/Widgets/PlatformMovementsStatsWidget.php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Platform;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\DB;

class PlatformMovementsStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = [];
        $today = now()->toDateString();

        // Obtener todas las plataformas
        $platforms = Platform::all();

        // Obtener los movimientos del día agrupados por plataforma
        $todayMovements = FinancialMovement::query()
            ->whereDate('financial_movements.date', $today) //  Especificar la tabla
            ->whereNotNull('platform_movement_id')
            ->select(
                'platform_movements.platform_id',
                DB::raw('SUM(CASE WHEN financial_movements.type = "income" THEN financial_movements.amount ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN financial_movements.type = "expense" THEN financial_movements.amount ELSE 0 END) as total_expense'),
                DB::raw('SUM(CASE WHEN financial_movements.type = "income" THEN financial_movements.amount ELSE -financial_movements.amount END) as balance')
            )
            ->join('platform_movements', 'financial_movements.platform_movement_id', '=', 'platform_movements.id')
            ->whereNull('platform_movements.deleted_at')
            ->groupBy('platform_movements.platform_id')
            ->get()
            ->keyBy('platform_id');

        // Crear estadísticas para cada plataforma
        foreach ($platforms as $platform) {
            $movement = $todayMovements->get($platform->id);
            
            $income = $movement ? (float) $movement->total_income : 0;
            $expense = $movement ? (float) $movement->total_expense : 0;
            $balance = $movement ? (float) $movement->balance : 0;

            // Determinar color según el saldo
            $color = match(true) {
                $balance > 0 => 'success',
                $balance < 0 => 'danger',
                default => 'gray',
            };

            // Determinar ícono según el saldo
            $icon = match(true) {
                $balance > 0 => 'heroicon-o-arrow-trending-up',
                $balance < 0 => 'heroicon-o-arrow-trending-down',
                default => 'heroicon-o-minus',
            };

            $stats[] = Stat::make(
                $platform->name ?? 'Plataforma #' . $platform->id,
                '$' . number_format($balance, 0, ',', '.')
            )
            ->description('Ingresos: $' . number_format($income, 0, ',', '.') . ' | Egresos: $' . number_format($expense, 0, ',', '.'))
            ->descriptionIcon($icon)
            ->color($color);
        }

        // Agregar un totalizador general
        $totalIncome = $todayMovements->sum('total_income');
        $totalExpense = $todayMovements->sum('total_expense');
        $totalBalance = $totalIncome - $totalExpense;

        $stats[] = Stat::make(
            '💰 TOTAL GENERAL',
            '$' . number_format($totalBalance, 0, ',', '.')
        )
        ->description('Total del día: ' . now()->format('d/m/Y'))
        ->descriptionIcon($totalBalance >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
        ->color($totalBalance >= 0 ? 'success' : 'danger');

        return $stats;
    }

    protected function getColumns(): int
    {
        return 2;
    }
}