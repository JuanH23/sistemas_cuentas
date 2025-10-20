<?php

namespace App\Filament\App\Resources\PlatformMovementResource\Pages;

use App\Filament\App\Resources\PlatformMovementResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\EditRecord;
use App\Models\FinancialMovement;

class EditPlatformMovement extends EditRecord
{
    protected static string $resource = PlatformMovementResource::class;

    protected function afterSave(): void
    {
        $platformMovement = $this->record;

        // Buscar el movimiento financiero relacionado
        $movement = FinancialMovement::where('platform_movement_id', $platformMovement->id)->first();

        if ($movement) {
            $movement->update([
                'amount'      => $platformMovement->amount,
                'description' => $platformMovement->description ?? 'Actualización de movimiento en plataforma',
                'category'    => 'Plataforma: ' . $platformMovement->platform->name,
                'type'        => $platformMovement->platformMovementType->direction ?? 'expense',
                'date'        => $platformMovement->date,
            ]);

            // Recalcular el saldo final del flujo de caja
            if ($movement->cashFlow) {
                $movement->cashFlow->recalculateFinalBalance();
            }
        }
    }
}
