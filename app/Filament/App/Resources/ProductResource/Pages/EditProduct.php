<?php

namespace App\Filament\App\Resources\ProductResource\Pages;

use App\Filament\App\Resources\ProductResource;

use App\Filament\App\Resources;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si no es inventario inicial, limpiar fecha de adquisición
        if (!($data['is_initial_inventory'] ?? false)) {
            $data['acquisition_date'] = null;
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        $product = $this->record;
        
        // ============================================
        // CASO 1: Es un servicio
        // ============================================
        if ($product->type !== 'producto') {
            // Si tenía movimiento financiero (antes era producto), eliminarlo
            if ($product->financialMovement) {
                $cashFlow = $product->financialMovement->cashFlow;
                $product->financialMovement->delete();
                $product->financial_movement_id = null;
                $product->save();
                
                if ($cashFlow) {
                    $cashFlow->recalculateFinalBalance();
                }
                
                Notification::make()
                    ->title('🔧 Convertido a Servicio')
                    ->body('El movimiento financiero asociado fue eliminado del flujo de caja')
                    ->info()
                    ->duration(5000)
                    ->send();
            }
            
            return;
        }
        
        // ============================================
        // CASO 2: Es inventario inicial
        // ============================================
        if ($product->is_initial_inventory) {
            // Si tenía movimiento financiero (antes era compra nueva), eliminarlo
            if ($product->financialMovement) {
                $cashFlow = $product->financialMovement->cashFlow;
                $product->financialMovement->delete();
                $product->financial_movement_id = null;
                $product->save();
                
                if ($cashFlow) {
                    $cashFlow->recalculateFinalBalance();
                }
                
                Notification::make()
                    ->title('📦 Marcado como Inventario Inicial')
                    ->body('El movimiento financiero fue removido del flujo de caja')
                    ->success()
                    ->duration(5000)
                    ->send();
            }
            
            return;
        }
        
        // ============================================
        // CASO 3: Es compra nueva (producto normal)
        // ============================================
        
        $userId = Auth::id();
        $today = now()->toDateString();
        
        try {
            // Si ya tiene movimiento financiero, actualizarlo
            if ($product->financialMovement) {
                $product->financialMovement->update([
                    'amount' => $product->price,
                    'description' => 'Actualización de ' . $product->quantity . ' x ' . $product->name,
                ]);
                
                // Recalcular saldo del flujo de caja asociado
                $product->financialMovement->cashFlow->recalculateFinalBalance();
                
                Notification::make()
                    ->title('💰 Movimiento Actualizado')
                    ->body("El egreso en flujo de caja se actualizó a $" . number_format($product->price, 0, ',', '.'))
                    ->success()
                    ->duration(5000)
                    ->send();
                
            } else {
                // Si NO tiene movimiento (era inventario inicial y ahora es compra nueva)
                // Crear nuevo movimiento financiero
                
                $cashFlow = CashFlow::getOrCreateToday($userId, $today);
                
                $movement = FinancialMovement::create([
                    'date'          => $today,
                    'category'      => 'Compra de producto',
                    'description'   => 'Compra de ' . $product->quantity . ' x ' . $product->name,
                    'amount'        => $product->price,
                    'type'          => 'expense',
                    'cash_flow_id'  => $cashFlow->id,
                    'user_id'       => $userId,
                ]);
                
                $product->financial_movement_id = $movement->id;
                $product->save();
                
                $cashFlow->recalculateFinalBalance();
                
                Notification::make()
                    ->title('🛒 Convertido a Compra Nueva')
                    ->body("Se creó un egreso de $" . number_format($product->price, 0, ',', '.') . " en el flujo de caja de hoy")
                    ->warning()
                    ->duration(6000)
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('⚠️ Error al Actualizar Flujo de Caja')
                ->body('El producto se actualizó pero hubo un error en el registro contable: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
            
            \Log::error('Error al actualizar movimiento financiero en edición de producto', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return '✅ Producto actualizado';
    }
}