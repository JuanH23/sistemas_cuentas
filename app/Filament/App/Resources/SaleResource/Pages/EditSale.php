<?php

namespace App\Filament\App\Resources\SaleResource\Pages;

use App\Filament\App\Resources\SaleResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\EditRecord;
use App\Models\FinancialMovement;
use App\Models\Product;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function beforeSave(): void
    {
        // Reponer el stock original antes de guardar
        $originalDetails = $this->record->saleDetails;

        foreach ($originalDetails as $detail) {
            $product = $detail->product;
            if ($product) {
                $product->increment('quantity', $detail->quantity);
            }
        }
    }

    protected function afterSave(): void
    {
        $sale = $this->record;

        // Descontar stock según los nuevos detalles
        foreach ($sale->saleDetails as $detail) {
            $product = $detail->product;
            if ($product) {
                $product->decrement('quantity', $detail->quantity);
            }
        }

        // Actualizar movimiento financiero
        $movement = FinancialMovement::where('sale_id', $sale->id)->first();

        if ($movement) {
            $movement->update([
                'amount'      => $sale->total_amount,
                'description' => 'Venta actualizada: ' . $sale->customer_name,
            ]);

            if ($movement->cashFlow) {
                $movement->cashFlow->recalculateFinalBalance();
            }
        }
    }
}
