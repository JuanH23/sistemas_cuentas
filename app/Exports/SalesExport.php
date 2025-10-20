<?php

namespace App\Exports;

use App\Models\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesExport implements FromCollection, WithHeadings
{
    protected $reportDate;

    public function __construct($reportDate)
    {
        $this->reportDate = $reportDate;
    }

    public function collection()
    {
        // Definimos el rango del día
        $start = Carbon::parse($this->reportDate)->startOfDay();
        $end = Carbon::parse($this->reportDate)->endOfDay();

        // Retornamos las ventas filtradas; ajusta los campos que deseas exportar
        return Sale::whereBetween('sale_date', [$start, $end])
            ->get(['id', 'customer_name', 'sale_date', 'total_amount']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Fecha',
            'Monto Total'
        ];
    }
}
