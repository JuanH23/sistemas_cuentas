<?php

namespace App\Filament\App\Resources\AccountReceivableResource\Pages;

use App\Filament\App\Resources\AccountReceivableResource;

use App\Filament\App\Resources;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\Auth;

class CreateAccountReceivable extends CreateRecord
{
    protected static string $resource = AccountReceivableResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Opcionalmente, puedes inicializar otros campos (por ejemplo, paid_amount en 0 si no viene)
        if (!isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // $account representa el registro de la cuenta por cobrar recién creado
        $account = $this->record;
        $userId = Auth::id();
        // Se obtiene la fecha, asegurando que sea una cadena de fecha (YYYY-MM-DD)
        $date = $account->created_at instanceof \Carbon\Carbon
        ? $account->created_at->toDateString()
        : now()->toDateString();
        // Buscar o crear el flujo de caja del día para el usuario
        $cashFlow = CashFlow::getOrCreateToday($userId, $date);
        
            
        /**
         * Si el registro de la venta a crédito registra un pago (paid_amount > 0)
         * se debe crear el movimiento financiero correspondiente.
         * Por ejemplo, si el cliente abona parte del total, se registra un ingreso.
         */
        if ($account->paid_amount > 0) {
            
            FinancialMovement::create([
                'date'           => $date,
                'category'       => 'Debe: ' . $account->client_name, // Puedes personalizar esta descripción
                'description'    => 'Abono a cuenta por cobrar', // Se puede enriquecer según tus necesidades
                'amount'         => $account->paid_amount,
                'type'           => 'income',
                'cash_flow_id'   => $cashFlow->id,
                'user_id'        => $userId,
                // Relacionamos el movimiento con la cuenta por cobrar para facilitar auditorías y conciliaciones
                'account_receivable_id' => $account->id,
            ]);

            // Recalcular el saldo final del flujo de caja luego de registrar el movimiento
            $cashFlow->recalculateFinalBalance();
        }
    }

}
