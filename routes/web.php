<?php

use Illuminate\Support\Facades\Route;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CashClosure;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\SaleReceiptController;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/sales/receipt/{id}/pdf', [SaleReceiptController::class, 'downloadPDF'])
        ->name('sales.receipt.pdf');
    
    Route::get('/sales/receipt/{id}/view', [SaleReceiptController::class, 'viewPDF'])
        ->name('sales.receipt.view');
        
    Route::get('/sales-export/{date}', function ($date) {
        // Se descarga el archivo Excel con el nombre "sales_{fecha}.xlsx"
        return Excel::download(new SalesExport($date), 'sales_' . $date . '.xlsx');
    });
    Route::get('/descargar-pdf-cierre/{filename}', function ($filename) {
        $path = storage_path("app/public/temp-pdfs/{$filename}");
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    });
});
