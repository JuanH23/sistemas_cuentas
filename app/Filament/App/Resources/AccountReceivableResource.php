<?php

namespace App\Filament\App\Resources;

use App\Models\AccountReceivable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Filament\App\Resources\AccountReceivableResource\Pages;

class AccountReceivableResource extends Resource
{
    protected static ?string $model = AccountReceivable::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Finanzas';

    public static function getModelLabel(): string
    {
        return 'cuenta por cobrar';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'cuentas por cobrar';
    }
    public static function canEdit($record): bool
    {
        // Bloquea la edición si el estado es "pagado"
        return $record->status !== 'pagado';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')->label('Factura'),
                Forms\Components\TextInput::make('client_name')->required()->label('Nombre del Cliente'),
                Forms\Components\TextInput::make('total_amount')->label('Total')->numeric()->required(),
                Forms\Components\DatePicker::make('due_date')
                ->label('Fecha a pagar')
                ->required()                                    
                ->native(false)
                ->closeOnDateSelection()
                ->displayFormat('d/m/Y')
                ->placeholder('Selecciona una fecha')
                ->suffixIcon('heroicon-o-calendar'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial'  => 'Parcial',
                        'pagado'   => 'Pagado',
                        'vencido'  => 'Vencido'
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Factura'),
                Tables\Columns\TextColumn::make('client_name')->label('Cliente'),
                Tables\Columns\TextColumn::make('total_amount')->label('Total'),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pagado'),
                Tables\Columns\TextColumn::make('remaining')
                ->label('Restante a Pagar')
                ->getStateUsing(fn ($record) => $record->total_amount - $record->paid_amount),
                Tables\Columns\TextColumn::make('due_date')->date()->label('Vencimiento'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])            
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial'  => 'Parcial',
                        'pagado'   => 'Pagado',
                        'vencido'  => 'Vencido',
                    ])
                    ->label('Estado'),
                Filter::make('Fecha de Vencimiento')->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('Desde')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->displayFormat('d/m/Y')
                        ->placeholder('Selecciona una fecha')
                        ->suffixIcon('heroicon-o-calendar'),
                    Forms\Components\DatePicker::make('to')
                        ->label('Hasta')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->displayFormat('d/m/Y')
                        ->placeholder('Selecciona una fecha')
                        ->suffixIcon('heroicon-o-calendar'),
                ])->query(fn ($query, array $data) => $query
                    ->when($data['from'] ?? null, fn($query, $date) => $query->whereDate('due_date', '>=', $date))
                    ->when($data['to'] ?? null, fn($query, $date) => $query->whereDate('due_date', '<=', $date))
                ),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccountReceivables::route('/'),
            'create' => Pages\CreateAccountReceivable::route('/create'),
            'edit'   => Pages\EditAccountReceivable::route('/{record}/edit'),
        ];
    }
}
