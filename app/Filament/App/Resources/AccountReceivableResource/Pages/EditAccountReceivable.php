<?php

namespace App\Filament\App\Resources\AccountReceivableResource\Pages;

use App\Filament\App\Resources\AccountReceivableResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action; // Importa la acción desde el namespace correcto
use Filament\Forms\Components\TextInput;

class EditAccountReceivable extends EditRecord
{
    protected static string $resource = AccountReceivableResource::class;

    protected function getHeaderActions(): array
    {
        if ($this->record->status === 'pagado') {
            return [];
        }
        return [
            Action::make('Registrar Abono')
                ->action(fn ($record, array $data) => $record->registerPayment($data))
                ->form([
                    TextInput::make('abono')
                        ->label('Monto del Abono')
                        ->numeric()
                        ->required(),
                ])
                ->modalHeading('Registrar Abono')
                ->modalButton('Aplicar Abono')
                ->requiresConfirmation(),
        ];
    }
}
