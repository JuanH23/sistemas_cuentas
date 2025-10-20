<?php


namespace App\Filament\App\Resources\AccountPayableResource\Pages;

use App\Filament\App\Resources\AccountPayableResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action;
use Filament\Forms\Components\TextInput;

class EditAccountPayable extends EditRecord
{
    protected static string $resource = AccountPayableResource::class;

    // Aquí definimos las acciones que se mostrarán en el encabezado de la página de edición.
    protected function getHeaderActions(): array
    {
        if ($this->record->status === 'pagado') {
            return [];
        }
        return [
            Action::make('Registrar Pago')
                ->action(fn ($record, array $data) => $record->registerPayment($data))
                ->form([
                    TextInput::make('pago')
                        ->label('Monto del Pago')
                        ->numeric()
                        ->required(),
                ])
                ->modalHeading('Registrar Pago')
                ->modalButton('Aplicar Pago')
                ->requiresConfirmation(),
        ];
    }
}
