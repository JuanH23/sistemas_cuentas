<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AccountPayable extends Model
{
    protected $table = 'accounts_payable';

    protected $fillable = [
        'provider_name',
        'invoice_number',
        'total_amount',
        'paid_amount',
        'due_date',
        'status',
    ];

    /**
     * Registra un pago (abono) en la cuenta por pagar.
     *
     * @param array $data Array que debe contener la clave 'pago'
     */
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
        // Convertir el monto ingresado a número flotante
        $pago = (float)$data['pago'];

        // Acumular el pago en paid_amount
        $this->paid_amount += $pago;

        // Actualizar el estado según el monto abonado
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'pagado';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'parcial';
        } else {
            $this->status = 'pendiente';
        }
        $this->save();

        // Registrar el movimiento financiero del pago:
        // Se asume que la cuenta por pagar generará un movimiento de egreso (salida de efectivo)
        $userId = Auth::id();
        $date = now()->toDateString();

        // Suponiendo que ya tienes implementado CashFlow y FinancialMovement.
        // Se obtiene o crea el flujo de caja del día para el usuario.
        $cashFlow = CashFlow::getOrCreateToday($userId, $date);

        // Crear el movimiento financiero. Nota: Asegúrate de que en la migración de financial_movements
        // exista la columna account_payable_id si deseas relacionarlo.
        \App\Models\FinancialMovement::create([
            'date'                => $date,
            'category'            => 'Pago a CP: ' . $this->provider_name,
            'description'         => 'Pago registrado a la cuenta por pagar',
            'amount'              => $pago,
            'type'                => 'expense', // Se registra como egreso
            'cash_flow_id'        => $cashFlow->id,
            'user_id'             => $userId,
            'account_payable_id'  => $this->id,
        ]);

        // Actualizar el flujo de caja, restando el pago (egreso)
        $cashFlow->recalculateFinalBalance();

        // Enviar notificación de éxito
        Notification::make()
            ->title('Pago Registrado')
            ->body("Se registró un pago de $pago a la cuenta por pagar de {$this->provider_name}.")
            ->success()
            ->send();
    }
}
