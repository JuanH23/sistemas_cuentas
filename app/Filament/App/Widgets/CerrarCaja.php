<?php
// app/Filament/App/Widgets/CerrarCaja.php

namespace App\Filament\App\Widgets;

use App\Models\CashFlow;
use App\Models\CashClosure;
use App\Models\FinancialMovement;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CerrarCaja extends Widget
{
    protected static string $view = 'filament.widgets.cerrar-caja';
    
    protected int | string | array $columnSpan = 'full';

    public ?float $realBalance = null;
    public bool $showConfirmation = false;
    public bool $isProcessing = false;
    
    // Datos del día
    public ?float $expectedBalance = null;
    public ?float $totalIncome = null;
    public ?float $totalExpense = null;
    public ?int $transactionCount = null;
    public ?string $cashFlowDate = null;
    public bool $hasCashFlow = false;
    public bool $alreadyClosed = false;

    public function mount(): void
    {
        $this->loadCashFlowData();
    }

    public function loadCashFlowData(): void
    {
        $user = auth()->user();
        $cashFlow = CashFlow::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if (!$cashFlow) {
            $this->hasCashFlow = false;
            return;
        }

        $this->hasCashFlow = true;
        $this->cashFlowDate = $cashFlow->date->format('d/m/Y');

        // Verificar si ya hay cierre
        $this->alreadyClosed = CashClosure::where('user_id', $cashFlow->user_id)
            ->whereDate('date', $cashFlow->date)
            ->exists();

        // Calcular totales del día
        // $movements = FinancialMovement::where('cash_flow_id', $cashFlow->id)->get();
        $movements = FinancialMovement::where('cash_flow_id', $cashFlow->id)
        ->where(function($query) {
            $query->whereNull('sale_id') // No tiene venta relacionada
                  ->orWhereHas('sale', function($q) {
                      $q->whereNull('deleted_at'); // La venta NO está eliminada
                  });
        })
        ->where(function($query) {
            $query->whereNull('platform_movement_id') // No tiene platform_movement
                  ->orWhereHas('platformMovement', function($q) {
                      $q->whereNull('deleted_at'); // El platform_movement NO está eliminado
                  });
        })
        ->get();
    
        $this->totalIncome = $movements->where('type', 'income')->sum('amount');
        $this->totalExpense = $movements->where('type', 'expense')->sum('amount');
        $this->expectedBalance = $cashFlow->initial_balance + $this->totalIncome - $this->totalExpense;
        $this->transactionCount = $movements->count();
        
        // Pre-llenar el saldo real con el esperado
        $this->realBalance = $this->expectedBalance;
    }

    public function prepararCierre(): void
    {
        if (!$this->hasCashFlow) {
            Notification::make()
                ->title('No hay caja activa')
                ->body('No se encontró una caja abierta para el día de hoy.')
                ->warning()
                ->send();
            return;
        }

        if ($this->alreadyClosed) {
            Notification::make()
                ->title('Caja ya cerrada')
                ->body('Ya existe un cierre de caja para el día de hoy.')
                ->warning()
                ->send();
            return;
        }

        $this->showConfirmation = true;
    }

    public function cancelarCierre(): void
    {
        $this->showConfirmation = false;
        $this->realBalance = $this->expectedBalance;
    }

    public function confirmarCierre(): void
    {
        // Validación
        if ($this->realBalance === null) {
            Notification::make()
                ->title('Saldo real requerido')
                ->body('Por favor ingrese el saldo real contado en caja.')
                ->warning()
                ->send();
            return;
        }

        $this->isProcessing = true;

        try {
            $user = auth()->user();
            $cashFlow = CashFlow::where('user_id', $user->id)
                ->whereDate('date', today())
                ->first();

            if (!$cashFlow) {
                throw new \Exception('No se encontró caja activa');
            }

            DB::beginTransaction();

            $closure = $cashFlow->generateClosure(realBalance: $this->realBalance);

            if (!$closure) {
                throw new \Exception('Ya existe un cierre para hoy');
            }

            $cashFlow->update(['final_balance' => $closure->real_balance]);

            DB::commit();

            // Calcular diferencia
            $difference = $this->realBalance - $this->expectedBalance;
            $differenceText = $difference != 0 
                ? ($difference > 0 
                    ? 'Sobrante: $' . number_format(abs($difference), 0, ',', '.') 
                    : 'Faltante: $' . number_format(abs($difference), 0, ',', '.'))
                : 'Sin diferencias';

            Notification::make()
                ->title('✅ Caja cerrada exitosamente')
                ->body($differenceText)
                ->success()
                ->duration(5000)
                ->send();

            // Generar PDF
            $this->generarPDF($cashFlow, $closure);

            // Resetear estado
            $this->showConfirmation = false;
            $this->isProcessing = false;
            $this->loadCashFlowData();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('❌ Error al cerrar caja')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->isProcessing = false;
        }
    }

    protected function generarPDF($cashFlow, $closure): void
    {
        try {
            $movements = $cashFlow->financialMovements()->orderBy('created_at')->get();
            
            // Verificar que los datos existan
            if (!$cashFlow || !$closure) {
                throw new \Exception('Datos incompletos para generar el PDF');
            }

            // Generar el PDF
            $pdf = Pdf::loadView('reports.cash-closure', [
                'cashFlow' => $cashFlow,
                'closure' => $closure,
                'movements' => $movements,
            ])
            ->setPaper('letter')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10);

            $filename = 'cierre-' . now()->format('Ymd-His') . '.pdf';
            $directory = storage_path('app/public/temp-pdfs');
            $path = "{$directory}/{$filename}";
            
            // Crear directorio si no existe
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Guardar el PDF
            $pdf->save($path);
            
            // Verificar que el archivo se creó correctamente
            if (!file_exists($path)) {
                throw new \Exception('No se pudo guardar el archivo PDF');
            }

            // Generar la URL pública
            $publicUrl = asset("storage/temp-pdfs/{$filename}");
            
            // Enviar evento para abrir/descargar el PDF
            $this->dispatch('abrir-pdf', $publicUrl);
            
            Notification::make()
                ->title('📄 PDF generado correctamente')
                ->body('El reporte se abrirá en una nueva pestaña.')
                ->success()
                ->duration(5000)
                ->send();
                
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de cierre de caja', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cash_flow_id' => $cashFlow->id ?? null,
                'closure_id' => $closure->id ?? null,
            ]);
            
            Notification::make()
                ->title('⚠️ Error al generar PDF')
                ->body('Detalles: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function getDifference(): float
    {
        if ($this->realBalance === null || $this->expectedBalance === null) {
            return 0;
        }
        return $this->realBalance - $this->expectedBalance;
    }
}