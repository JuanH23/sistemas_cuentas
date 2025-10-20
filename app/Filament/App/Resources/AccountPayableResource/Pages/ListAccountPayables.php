<?php

namespace App\Filament\App\Resources\AccountPayableResource\Pages;

use App\Filament\App\Resources\AccountPayableResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\Action;
use Filament\Forms;

class ListAccountPayables extends ListRecords
{
    protected static string $resource = AccountPayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Action::make('Registrar Pago')
                ->action(fn ($record, array $data) => $record->registerPayment($data))
                ->form([
                    Forms\Components\TextInput::make('pago')
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