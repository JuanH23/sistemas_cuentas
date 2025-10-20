<?php

namespace App\Filament\App\Resources\SaleResource\Pages;

use App\Filament\App\Resources\SaleResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * Redirigir al listado después de crear
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Título personalizado de la notificación de éxito
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Venta registrada exitosamente';
    }

    /**
     * ================================================================
     * VALIDACIÓN PREVIA: Verificar stock ANTES de guardar
     * ================================================================
     * Esto previene que se guarden ventas con stock insuficiente
     */
    protected function beforeCreate(): void
    {
        $saleDetails = $this->data['sale_details'] ?? [];

        // Validar cada producto antes de crear la venta
        foreach ($saleDetails as $detail) {
            $productId = $detail['product_id'] ?? null;
            $quantityRequested = $detail['quantity'] ?? 0;

            if (!$productId) {
                continue;
            }

            $product = Product::find($productId);
            
            if (!$product) {
                Notification::make()
                    ->title('Error: Producto no encontrado')
                    ->body("El producto seleccionado no existe en el sistema.")
                    ->danger()
                    ->persistent()
                    ->send();
                
                $this->halt();
                return;
            }

            // Solo validar stock para productos físicos (no servicios)
            if ($product->type === 'producto') {
                if ($quantityRequested > $product->quantity) {
                    Notification::make()
                        ->title('Stock Insuficiente')
                        ->body(sprintf(
                            "No hay suficiente stock de '%s'.\n\nDisponible: %d unidades\nSolicitado: %d unidades\n\nPor favor, ajusta la cantidad.",
                            $product->name,
                            $product->quantity,
                            $quantityRequested
                        ))
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    $this->halt();
                    return;
                }

                // Advertencia si se consume más del 80% del stock disponible
                $percentageUsed = ($quantityRequested / $product->quantity) * 100;
                
                if ($percentageUsed >= 80 && $percentageUsed < 100) {
                    Notification::make()
                        ->title('Advertencia: Alto Consumo de Stock')
                        ->body(sprintf(
                            "Estás vendiendo el %d%% del stock disponible de '%s'.\n\nQuedará: %d unidades después de esta venta.",
                            round($percentageUsed),
                            $product->name,
                            $product->quantity - $quantityRequested
                        ))
                        ->warning()
                        ->send();
                }
            }
        }
    }

    /**
     * ================================================================
     * DESPUÉS DE CREAR: Tu lógica actual + mejoras
     * ================================================================
     * 1. Descontar stock de productos
     * 2. Crear movimiento financiero (tu lógica actual)
     * 3. Recalcular flujo de caja (tu lógica actual)
     * 4. Enviar notificaciones de stock bajo/agotado
     */
    protected function afterCreate(): void
    {
        $sale = $this->record;
        $user = Auth::user();
        $today = now()->toDateString();
        
        // Arrays para rastrear productos con alertas
        $lowStockProducts = [];
        $outOfStockProducts = [];

        // ============================================
        // 1. DESCONTAR STOCK DE PRODUCTOS
        // ============================================
        foreach ($sale->saleDetails as $detail) {
            $product = $detail->product;
            
            if ($product && $product->type === 'producto') {
                // Descontar la cantidad vendida del stock
                $product->decrement('quantity', $detail->quantity);
                
                // Recargar el producto para obtener el stock actualizado
                $product->refresh();
                
                // Verificar si el stock quedó bajo (menos de 5 unidades)
                if ($product->quantity > 0 && $product->quantity <= 5) {
                    $lowStockProducts[] = [
                        'name' => $product->name,
                        'quantity' => $product->quantity
                    ];
                }
                
                // Verificar si el producto se agotó completamente
                if ($product->quantity <= 0) {
                    $outOfStockProducts[] = $product->name;
                }
            }
        }

        // ============================================
        // 2. CREAR MOVIMIENTO FINANCIERO
        // (Tu lógica actual - SIN CAMBIOS)
        // ============================================
        $cashFlow = CashFlow::getOrCreateToday($user->id, $today);

        FinancialMovement::create([
            'date'          => $today,
            'category'      => 'Venta de productos',
            'description'   => 'Venta a ' . ($sale->customer_name ?? 'Cliente anónimo') . 
                            ($sale->payment_method ? ' - ' . ucfirst($sale->payment_method) : ''),
            'amount'        => $sale->total_amount,
            'type'          => FinancialMovement::TYPE_INCOME,
            'cash_flow_id'  => $cashFlow->id,
            'user_id'       => $user->id,
            'sale_id'       => $sale->id,
        ]);

        // ============================================
        // 3. RECALCULAR SALDO DEL FLUJO DE CAJA
        // (Tu lógica actual - SIN CAMBIOS)
        // ============================================
        $cashFlow->recalculateFinalBalance();

        // ============================================
        // 4. NOTIFICACIONES DE ALERTAS DE STOCK
        // ============================================
        
        // Notificar productos con stock bajo
        if (!empty($lowStockProducts)) {
            $productList = collect($lowStockProducts)
                ->map(fn($p) => "• {$p['name']} (quedan {$p['quantity']} unidades)")
                ->join("\n");
            
            Notification::make()
                ->title('Alerta: Stock Bajo')
                ->body("Los siguientes productos necesitan reabastecimiento:\n\n" . $productList)
                ->warning()
                ->persistent()
                ->sendToDatabase($user);
        }

        // Notificar productos agotados
        if (!empty($outOfStockProducts)) {
            $productList = collect($outOfStockProducts)
                ->map(fn($name) => "• {$name}")
                ->join("\n");
            
            Notification::make()
                ->title('Productos Agotados')
                ->body("Los siguientes productos se han agotado y requieren reabastecimiento urgente:\n\n" . $productList)
                ->danger()
                ->persistent()
                ->sendToDatabase($user);
        }

        // ============================================
        // 5. NOTIFICACIÓN DE ÉXITO CON RESUMEN
        // ============================================
        $totalItems = $sale->saleDetails->sum('quantity');
        $paymentMethod = match($sale->payment_method ?? 'cash') {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'credit' => 'Crédito',
            default => 'Efectivo'
        };

        Notification::make()
            ->title('Venta Completada')
            ->body(sprintf(
                "Cliente: %s\nTotal: $%s\nItems: %d\nPago: %s",
                $sale->customer_name ?? 'Sin nombre',
                number_format($sale->total_amount, 0, ',', '.'),
                $totalItems,
                $paymentMethod
            ))
            ->success()
            ->duration(5000)
            ->send();

        // ============================================
        // 6. NOTIFICACIÓN CON ACCIONES DE RECIBO
        // (NUEVA FUNCIONALIDAD - NO INTERFIERE CON TU LÓGICA)
        // ============================================
        Notification::make()
            ->title('Recibo Disponible')
            ->body(sprintf(
                "Recibo #%s generado\n\nPuedes ver, descargar o enviar el recibo al cliente.",
                str_pad($sale->id, 6, '0', STR_PAD_LEFT)
            ))
            ->info()
            ->actions([
                Action::make('view')
                    ->label('Ver Recibo')
                    ->icon('heroicon-o-document-text')
                    ->url(route('sales.receipt.view', $sale->id))
                    ->openUrlInNewTab(),
                
                Action::make('download')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('sales.receipt.pdf', $sale->id))
                    ->openUrlInNewTab(),
                
                Action::make('email')
                    ->label('Enviar Email')
                    ->icon('heroicon-o-envelope')
                    ->visible(!empty($sale->customer_email))
                    ->action(function () use ($sale) {
                        // Aquí puedes implementar el envío por email
                        Notification::make()
                            ->title('Función en desarrollo')
                            ->body('El envío por email estará disponible próximamente')
                            ->info()
                            ->send();
                    }),
            ])
            ->duration(15000) // 15 segundos para dar tiempo a hacer clic
            ->send();
    }
}