<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AccountReceivable extends Model
{
    use HasFactory;
    protected $table = 'accounts_receivable';

    protected $fillable = [
        'sale_id',
        'client_name',
        'invoice_number',
        'total_amount',
        'paid_amount',
        'due_date',
        'status',
    ];

    public function registerPayment(array $data): void
    {
        if ($this->status === 'pagado') {
            Notification::make()
                ->title('Acción no permitida')
                ->body('Esta cuenta por pagar ya se encuentra pagada y no se pueden registrar más pagos.')
                ->danger()
                ->send();
            return;
        }
        $abono = (float) $data['abono'];
        // Actualiza el paid_amount: suma el abono al monto ya abonado
        $this->paid_amount += $abono;
        
        // Actualiza el estado en función del pago
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'pagado';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'parcial';
        } else {
            $this->status = 'pendiente';
        }
        $this->save();
        
        // Registrar el movimiento financiero del abono
        $userId = Auth::id();
        // Usamos created_at del registro para determinar la fecha del abono,
        // o bien, usamos now() si deseas la fecha de pago actual.
        $date = $this->created_at instanceof \Carbon\Carbon
                  ? $this->created_at->toDateString()
                  : now()->toDateString();
        
        // Obtener el flujo de caja activo del día para el usuario (método getOrCreateToday ya definido en CashFlow)
        $cashFlow = CashFlow::getOrCreateToday($userId, $date);
        
        FinancialMovement::create([
            'date'                   => $date,
            'category'               => 'Abono a CxC: ' . $this->client_name,
            'description'            => 'Abono registrado a la cuenta por cobrar',
            'amount'                 => $abono,
            'type'                   => 'income', // o 'ingreso'
            'cash_flow_id'           => $cashFlow->id,
            'user_id'                => $userId,
            'account_receivable_id'  => $this->id,
        ]);
        
        // Recalcular el saldo del flujo de caja
        $cashFlow->recalculateFinalBalance();
        Notification::make()
        ->title('Abono Registrado')
        ->body("Se registró un abono de $abono a la cuenta por cobrar de {$this->client_name}.")
        ->success() // Opcional: para darle un estilo de éxito
        ->send();
    }
}
