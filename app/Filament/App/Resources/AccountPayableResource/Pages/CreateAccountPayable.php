<?php



namespace App\Filament\App\Resources\AccountPayableResource\Pages;
use App\Filament\App\Resources\AccountPayableResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;

class CreateAccountPayable extends CreateRecord
{
    protected static string $resource = AccountPayableResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se inicializa paid_amount en 0 si no se ha establecido
        if (!isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // $account es el registro de la cuenta por pagar recién creado
        $account = $this->record;
        $userId = Auth::id();
        // Usamos la fecha de creación para determinar la fecha (formato YYYY-MM-DD)
        $date = $account->created_at instanceof \Carbon\Carbon
            ? $account->created_at->toDateString()
            : now()->toDateString();

        // Para una cuenta por pagar, la simple creación no afecta el flujo de caja;
        // solo si se registra un pago (paid_amount > 0) se crea el movimiento financiero.
        if ($account->paid_amount > 0) {
            // Buscar o crear el flujo de caja del día para el usuario
            $cashFlow = CashFlow::getOrCreateToday($userId, $date);
            
            // Registrar el movimiento financiero del pago
            FinancialMovement::create([
                'date'                  => $date,
                'category'              => 'Pago a CP: ' . $account->provider_name,
                'description'           => 'Pago registrado a la cuenta por pagar',
                'amount'                => $account->paid_amount,
                'type'                  => 'expense', // Egreso (ya que se paga efectivo)
                'cash_flow_id'          => $cashFlow->id,
                'user_id'               => $userId,
                'account_payable_id'    => $account->id, // Asegúrate de tener esta columna en financial_movements
            ]);
            
            // Recalcular el saldo final del flujo de caja luego de registrar el egreso
            $cashFlow->recalculateFinalBalance();
        }
    }
}
