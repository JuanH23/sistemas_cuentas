<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleReceiptController extends Controller
{

    public function downloadPDF($id)
    {
        $sale = Sale::with(['saleDetails.product', 'financialMovement.user'])
            ->findOrFail($id);
        
        $pdf = Pdf::loadView('filament.pages.sale-receipt', ['sale' => $sale]);
        
        return $pdf->download('recibo-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }

    public function viewPDF($id)
    {
        $sale = Sale::with(['saleDetails.product', 'financialMovement.user'])
            ->findOrFail($id);
        
        $pdf = Pdf::loadView('filament.pages.sale-receipt', ['sale' => $sale]);
        
        return $pdf->stream();
    }
}
