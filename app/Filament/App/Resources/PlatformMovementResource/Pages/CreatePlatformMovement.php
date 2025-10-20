<?php

namespace App\Filament\App\Resources\PlatformMovementResource\Pages;

use App\Filament\App\Resources\PlatformMovementResource;

use App\Filament\App\Resources;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformMovement extends CreateRecord
{
    protected static string $resource = PlatformMovementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asigna usuario y fecha automáticamente
        $data['user_id'] = Auth::id();
        $data['date'] = now()->toDateString();

        return $data;
    }

    protected function afterCreate(): void
    {
        $movement = $this->record;
        $userId = Auth::id();
        $date = $movement->date instanceof \Carbon\Carbon
        ? $movement->date->toDateString()
        : $movement->date;
        // Buscar o crear el flujo de caja del día para el usuario
        $cashFlow = CashFlow::getOrCreateToday($userId, $date);

        // Asociar el flujo de caja al movimiento de plataforma
        $movement->update(['cash_flow_id' => $cashFlow->id]);

        // Determinar si es ingreso o egreso, usando la relación
        $direction = $movement->platformMovementType->direction ?? 'expense';

        // Crear el movimiento financiero asociado
        FinancialMovement::create([
            'date' => $movement->date,
            'category' => 'Plataforma: ' . $movement->platform->name,
            'description' => $movement->description ?? '',
            'amount' => $movement->amount,
            'type' => $direction,
            'cash_flow_id' => $cashFlow->id,
            'user_id' => $userId,
            'platform_movement_id' => $movement->id,
        ]);

        // Recalcular el saldo final del flujo de caja
        $cashFlow->recalculateFinalBalance();
    }
}
