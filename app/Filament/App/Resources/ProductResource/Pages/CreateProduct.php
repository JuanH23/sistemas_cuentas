<?php

namespace App\Filament\App\Resources\ProductResource\Pages;

use App\Filament\App\Resources\ProductResource;

use App\Filament\App\Resources;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification; 

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si no se especificó, por defecto NO es inventario inicial
        $data['is_initial_inventory'] = $data['is_initial_inventory'] ?? false;
        
        // Si no es inventario inicial, limpiar fecha de adquisición
        if (!$data['is_initial_inventory']) {
            $data['acquisition_date'] = null;
        }
        
        return $data;
    }


    protected function afterCreate(): void
    {
        $product = $this->record;
        // ============================================
        // VALIDACIÓN 1: ¿Es un servicio?
        // ============================================
        if ($product->type !== 'producto') {
            Notification::make()
                ->title('🔧 Servicio Registrado')
                ->body("El servicio '{$product->name}' se creó exitosamente")
                ->success()
                ->duration(4000)
                ->send();
            
            return; // Los servicios NO afectan el flujo de caja al crearlos
        }
        
        // ============================================
        //  VALIDACIÓN 2: ¿Es inventario inicial?
        // ============================================
        if ($product->is_initial_inventory) {
            $fechaAdquisicion = $product->acquisition_date 
                ? $product->acquisition_date->format('d/m/Y') 
                : 'fecha no especificada';
            
            Notification::make()
                ->title('📦 Inventario Inicial Registrado')
                ->body("El producto '{$product->name}' se agregó como inventario inicial ({$fechaAdquisicion}). NO se registró en el flujo de caja.")
                ->success()
                ->duration(6000)
                ->send();
            
            return; //  NO crear movimiento financiero
        }

        $product = $this->record;
        $userId = Auth::id();
        $today = now()->toDateString();

        // Crear o usar flujo de caja del día
        $cashFlow = CashFlow::getOrCreateToday($userId, $today);

        // Crear movimiento financiero como egreso
        $movement = FinancialMovement::create([
            'date'          => $today,
            'category'      => 'Compra de producto',
            'description'   => 'Compra de ' . $product->quantity . ' x ' . $product->name,
            'amount'        => $product->price,
            'type'          => 'expense',
            'cash_flow_id'  => $cashFlow->id,
            'user_id'       => $userId,
        ]);

        // Asociar el movimiento financiero al producto
        $this->record->financial_movement_id = $movement->id;
        $this->record->save();

        // Recalcular saldo
        $cashFlow->recalculateFinalBalance();
    }
}
