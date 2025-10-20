<?php

namespace App\Filament\App\Resources;

use App\Models\PlatformMovement;
use App\Models\CashFlow;
use App\Models\FinancialMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use App\Filament\App\Resources\PlatformMovementResource\Pages;

class PlatformMovementResource extends Resource
{
    protected static ?string $model = PlatformMovement::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?int $navigationSort = 3;
    
    public static function getModelLabel(): string
    {
        return 'Movimiento de Plataforma';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Movimientos de Plataforma';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // SECCIÓN: INFORMACIÓN DE LA TRANSACCIÓN
            Section::make('Información de la Transacción')
                ->description('Detalles de la plataforma y tipo de movimiento')
                ->icon('heroicon-o-credit-card')
                ->iconColor('primary')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('platform_id')
                            ->label('Plataforma')
                            ->relationship('platform', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la plataforma')
                                    ->placeholder('Ej: Nequi, Daviplata, PayPal')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-building-office-2'),
                            ])
                            ->createOptionModalHeading('Agregar nueva plataforma')
                            ->helperText('Selecciona o crea una plataforma de pago')
                            ->columnSpan(1),

                        Forms\Components\Select::make('platform_movement_type_id')
                            ->label('Tipo de Movimiento')
                            ->relationship('platformMovementType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-arrows-right-left')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $type = \App\Models\PlatformMovementType::find($state);
                                    if ($type) {
                                        $icon = $type->direction === 'income' ? '💰' : '💸';
                                        $msg = $type->direction === 'income' ? 'Ingreso' : 'Egreso';
                                        
                                        Notification::make()
                                            ->title("{$icon} Tipo: {$msg}")
                                            ->body($type->name)
                                            ->info()
                                            ->duration(3000)
                                            ->send();
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del tipo de movimiento')
                                    ->placeholder('Ej: Retiro, Depósito, Transferencia')
                                    ->required()
                                    ->maxLength(255),
                        
                                Forms\Components\Select::make('direction')
                                    ->label('Dirección del Movimiento')
                                    ->options([
                                        'income' => '💰 Ingreso (Entra dinero)',
                                        'expense' => '💸 Egreso (Sale dinero)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->helperText('Define si este tipo de movimiento representa entrada o salida de dinero'),
                            ])
                            ->createOptionModalHeading('Agregar nuevo tipo de movimiento')
                            ->editOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del tipo de movimiento')
                                    ->required()
                                    ->maxLength(255),
                        
                                Forms\Components\Select::make('direction')
                                    ->label('Dirección del Movimiento')
                                    ->options([
                                        'income' => '💰 Ingreso',
                                        'expense' => '💸 Egreso',
                                    ])
                                    ->required()
                                    ->native(false),
                            ])
                            ->editOptionModalHeading('Editar tipo de movimiento')
                            ->helperText('Define el tipo de operación realizada')
                            ->columnSpan(1),
                    ]),
                ])
                ->collapsible()
                ->compact(),

            // SECCIÓN: DETALLES DEL MOVIMIENTO
            Section::make('Detalles del Movimiento')
                ->description('Monto, referencia y datos adicionales')
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->minValue(0)
                            ->maxValue(999999999)
                            ->placeholder('0')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state > 1000000) {
                                    Notification::make()
                                        ->title('Monto Alto')
                                        ->body('Verifica que el monto sea correcto: $' . number_format($state, 0, ',', '.'))
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->helperText('Valor de la transacción')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('reference')
                            ->label('Número de Referencia')
                            ->placeholder('Ej: REF-12345, TRX-98765')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText('Código de la transacción (opcional)')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('operator')
                            ->label('Operador/Responsable')
                            ->placeholder('Nombre de quien realizó la operación')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Persona que ejecutó el movimiento (opcional)')
                            ->columnSpan(1),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Describe el motivo o detalles de este movimiento...')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->helperText('Detalles adicionales sobre la transacción'),
                ])
                ->collapsible()
                ->compact(),

            // SECCIÓN: ANEXOS Y DOCUMENTOS
            Section::make('Documentos Adjuntos')
                ->description('Soportes, comprobantes o evidencias')
                ->icon('heroicon-o-paper-clip')
                ->iconColor('warning')
                ->schema([
                    Forms\Components\FileUpload::make('anexo')
                        ->label('Archivo Adjunto')
                        ->directory('anexos/platform-movements')
                        ->image()
                        ->imageEditor()
                        ->downloadable()
                        ->openable()
                        ->preserveFilenames()
                        ->maxSize(5120) // 5MB
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                        ])
                        ->helperText('PDF, Word o imagen. Máximo 5MB')
                        ->columnSpanFull()
                        ->afterStateUpdated(function ($state, $component, $livewire) {
                            $record = $livewire->record ?? null;
                    
                            if ($record && $record->anexo && $record->anexo !== $state) {
                                \Storage::disk('public')->delete($record->anexo);
                            }
                            
                            if ($state) {
                                Notification::make()
                                    ->title('Archivo Cargado')
                                    ->body('El documento se ha adjuntado correctamente')
                                    ->success()
                                    ->send();
                            }
                        }),
                ])
                ->collapsible()
                ->collapsed()
                ->compact(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('platform.name')
                    ->label('Plataforma')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office-2')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('platformMovementType.name')
                    ->label('Tipo')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => 
                        $record->platformMovementType?->direction === 'income' ? 'success' : 'danger'
                    )
                    ->icon(fn ($record) => 
                        $record->platformMovementType?->direction === 'income' 
                            ? 'heroicon-o-arrow-down-circle' 
                            : 'heroicon-o-arrow-up-circle'
                    )
                    ->formatStateUsing(fn ($state, $record) => 
                        ($record->platformMovementType?->direction === 'income' ? '⬇️ ' : '⬆️ ') . $state
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => 
                        $record->platformMovementType?->direction === 'income' ? 'success' : 'danger'
                    )
                    ->description(fn ($record) => $record->reference ? "Ref: {$record->reference}" : null),

                Tables\Columns\TextColumn::make('operator')
                    ->label('Operador')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\IconColumn::make('anexo')
                    ->label('Anexo')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->relationship('platform', 'name')
                    ->label('Plataforma')
                    ->native(false)
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->relationship('platformMovementType', 'name')
                    ->label('Tipo de Movimiento')
                    ->native(false)
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('direction')
                    ->label('Dirección')
                    ->form([
                        Forms\Components\Select::make('direction')
                            ->label('Tipo')
                            ->options([
                                'income' => '💰 Ingresos',
                                'expense' => '💸 Egresos',
                            ])
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['direction'])) {
                            $query->whereHas('platformMovementType', function ($q) use ($data) {
                                $q->where('direction', $data['direction']);
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['direction'])) {
                            return $data['direction'] === 'income' ? 'Solo ingresos' : 'Solo egresos';
                        }
                        return null;
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->label('Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        Forms\Components\DatePicker::make('to')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('date', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['to'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['to'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('with_attachment')
                    ->label('Con Anexo')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('anexo'))
                    ->indicateUsing(fn () => ['Solo movimientos con documento adjunto']),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([


                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),

                    Tables\Actions\Action::make('view_attachment')
                        ->label('Ver Anexo')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->visible(fn ($record) => $record->anexo !== null)
                        ->url(fn ($record) => \Storage::url($record->anexo))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('restore')
                        ->label('Restaurar')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->action(fn ($record) => $record->restore())
                        ->visible(fn ($record) => $record->trashed())
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => !$record->trashed()),

                    Tables\Actions\Action::make('forceDelete')
                        ->label('Eliminar Definitivamente')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->action(fn ($record) => $record->forceDelete())
                        ->visible(fn ($record) => $record->trashed())
                        ->requiresConfirmation(),
                ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Exportar Selección')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Exportación en Proceso')
                                ->body(count($records) . ' registros seleccionados')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformMovements::route('/'),
            'create' => Pages\CreatePlatformMovement::route('/create'),
            'edit' => Pages\EditPlatformMovement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::whereDate('date', today())->count();
        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Movimientos de hoy';
    }
}