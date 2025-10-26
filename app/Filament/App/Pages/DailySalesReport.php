<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Pages\Actions\Action;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use App\Models\SaleDetail;
use App\Models\PlatformMovement;
use App\Models\Product;	
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DailySalesReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.daily-sales-report';
    // protected static string $view = 'filament.app.pages.daily-sales-report';  
    protected static ?string $title = 'Reportes';
    protected static ?string $navigationGroup = 'Reportes';

    public string $filtroPeriodo = 'hoy';

    // Propiedades para guardar KPIs numéricos
    public float $todaySalesTotal = 0;
    public float $monthSalesTotal = 0;
    public float $changeToday = 0;
    public ?float $cambioPorcentaje = null;
    public $weeklySales = [];
    public float  $periodSalesTotal   = 0;  // Total ventas en el período
    public float  $netFlow            = 0;  // Flujo neto en el período
    // Para las gráficas (por días / por horas, etc.)
    public $dailyTraffic = [];
    public $monthlyTraffic = [];
    public $topProducts = [];
    public $topPlatforms = [];
    public $weeklyInOut = [];
    public $saleDetails= [];
    public $products = [];

    public function mount(): void
    {
        $this->loadData();
    }

    // Al montar la página se ejecutan las consultas y se almacena la información.
    public function loadData(): void
    {
       switch ($this->filtroPeriodo) {
            case '7dias':
                $from = now()->subDays(6)->format('Y-m-d'); // '2025-10-19'
                $to   = now()->format('Y-m-d');              // '2025-10-25'
                break;
            case 'mes':
                $from = now()->startOfMonth()->format('Y-m-d'); // '2025-10-01'
                $to   = now()->endOfMonth()->format('Y-m-d');   // '2025-10-31'
                break;
            default: // 'hoy'
                $from = now()->format('Y-m-d'); // '2025-10-25'
                $to   = now()->format('Y-m-d'); // '2025-10-25'
        }
        // 2) Total ventas en el período
        $this->periodSalesTotal = Sale::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('total_amount');


        // 3) Cambio % vs día anterior (solo si el rango incluye hoy)
        $ayer = Sale::whereBetween('sale_date', [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ])->sum('total_amount');
        $diff = $this->periodSalesTotal - $ayer;
        $this->cambioPorcentaje = $ayer > 0
            ? round(($diff / $ayer) * 100, 1)
            : null;

        // 4) Flujo neto en el período
        $income  = FinancialMovement::where('type','income')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
        $expense = FinancialMovement::where('type','expense')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
        $this->netFlow = $income - $expense;
        // KPI 1: Ventas de hoy
        $this->todaySalesTotal = Sale::whereDate('sale_date', now())
            ->sum('total_amount');

        // KPI 2: Ventas del mes
        $this->monthSalesTotal = Sale::whereBetween('sale_date', [$from, $to])
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // KPI 3: Flujo neto (ingresos - egresos) este mes (si usas financial_movements)
        $income = FinancialMovement::where('type', 'income')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $expense = FinancialMovement::where('type', 'expense')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $this->netFlow = $income - $expense;

        $this->topProducts = SaleDetail::join('products', 'products.id', '=', 'sale_details.product_id') // Ajusta según tu sistema
        ->select('products.name', DB::raw('SUM(sale_details.quantity) as total_vendidos'))
        ->groupBy('products.name')
        ->orderByDesc('total_vendidos')
        ->limit(5)
        ->toBase() 
        ->get();

        // Plataformas más usadas (desde financial_movements, campo: payment_method o platform_name)
        $this->topPlatforms = PlatformMovement::join('platforms', 'platforms.id', '=', 'platform_movements.platform_id')
        ->select('platforms.name as platform', DB::raw('COUNT(*) as total_usos'))
        ->groupBy('platforms.name')
        ->orderByDesc('total_usos')
        ->limit(5)
        ->toBase() 
        ->get();

        $this->weeklySales = Sale::selectRaw('DATE(created_at) as fecha, SUM(total_amount) as total')
        ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('fecha')
        ->toBase() 
        ->get();

        $ventasAyer = Sale::whereDate('sale_date', now()->subDay())->sum('total_amount');
        $this->changeToday = $this->todaySalesTotal - $ventasAyer;
        if ($ventasAyer > 0) {
            $this->cambioPorcentaje = round(($this->changeToday / $ventasAyer) * 100, 1);
        } else {
            $this->cambioPorcentaje = null; // o 0, según lo que quieras mostrar
        }
        

        $this->weeklyInOut = FinancialMovement::selectRaw("
            DATE(created_at) as fecha,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as ingresos,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as egresos
        ")
        ->whereBetween('created_at', [now()->subDays(6), now()])
        ->groupBy('fecha')
        ->orderBy('fecha')
        ->toBase() 
        ->get();

        // 8) Detalle completo de ventas en el periodo
        $this->saleDetails = SaleDetail::with('product')
        ->whereBetween('sale_details.created_at', [$from, $to])
        ->get();


    }


    public function exportSalesPdf()
    {
        // Recalcular datos con el filtro actual
        $this->mount();

        // Generar PDF desde una vista Blade (crea resources/views/reports/sales-summary.blade.php)
        $pdf = Pdf::loadView('reports.sales-summary', [
            'saleDetails'        => $this->saleDetails,
        ])
        ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "reporte-ventas-{$this->filtroPeriodo}-".now()->format('Y-m-d').".pdf"
        );
    }
    public function exportIncomeExpensePdf()
    {
        // mete aquí la lógica de IncomeExpenseReport::mount()
        switch ($this->filtroPeriodo) {
            case '7dias':
                $from = now()->subDays(6)->startOfDay();
                $to   = now()->endOfDay();
                break;
            case 'mes':
                $from = now()->startOfMonth();
                $to   = now()->endOfMonth();
                break;
            default:
                $from = now()->startOfDay();
                $to   = now()->endOfDay();
        }

        $totalIncome  = \App\Models\FinancialMovement::where('type','income')
                          ->whereBetween('created_at',[$from,$to])->sum('amount');
        $totalExpense = \App\Models\FinancialMovement::where('type','expense')
                          ->whereBetween('created_at',[$from,$to])->sum('amount');
        $balance      = $totalIncome - $totalExpense;
        $byDate       = \App\Models\FinancialMovement::selectRaw("
                            DATE(created_at) as fecha,
                            type,
                            amount,
                            category,
                            description
                          ")
                          ->whereBetween('created_at',[$from,$to])
                          ->get();

        $pdf = Pdf::loadView('reports.income-expense', [
            'filtroPeriodo' => $this->filtroPeriodo,
            'totalIncome'   => $totalIncome,
            'totalExpense'  => $totalExpense,
            'balance'       => $balance,
            'byDate'        => $byDate,
        ])->setPaper('a4','portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "ingresos-egresos-{$this->filtroPeriodo}-".now()->format('Y-m-d').".pdf"
        );
    }
    public function exportInventoryPdf()
    {
        // Recargar productos
        $products = Product::select('id', 'name', 'quantity', 'price', 'unit_price')
            ->orderBy('name')
            ->get();

        // Generar PDF
        $pdf = Pdf::loadView('reports.inventory', [
            'products' => $products,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'inventario-'.now()->format('Y-m-d').'.pdf'
        );
    }
    public function updatedFiltroPeriodo(string $value): void
    {
        $this->loadData();
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
