<?php

namespace App\Filament\App\Resources\AccountReceivableResource\Pages;

use App\Filament\App\Resources\AccountReceivableResource;

use App\Filament\App\Resources;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Forms;

class ListAccountReceivables extends ListRecords
{
    protected static string $resource = AccountReceivableResource::class;

    // Acción en el encabezado para crear una nueva cuenta por cobrar
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
    
    // Se definen las acciones que se muestran para cada registro en la tabla
    protected function getTableActions(): array
    {
        return [
            Action::make('Registrar Abono')
                ->action(fn ($record, array $data) => $record->registerPayment($data))
                ->form([
                    Forms\Components\TextInput::make('abono')
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
