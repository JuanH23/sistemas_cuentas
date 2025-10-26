<?php

namespace App\Filament\App\Resources;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;
use App\Filament\App\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 2;
    
    public static function getModelLabel(): string
    {
        return 'Producto';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Productos';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ========== SECCIÓN INFORMACIÓN BÁSICA ==========
            Section::make('📦 Información General')
                ->description('Datos principales del producto o servicio')
                ->icon('heroicon-o-information-circle')
                ->iconColor('primary')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Item')
                            ->options([
                                'producto' => '📦 Producto Físico',
                                'servicio' => '🔧 Servicio',
                            ])
                            ->reactive()
                            ->default('producto')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-tag')
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // Recalcular cuando cambia el tipo
                                self::calculateUnitPrice($set, $get);
                                
                                // Si cambia a servicio, desactivar inventario inicial
                                if ($state === 'servicio') {
                                    $set('is_initial_inventory', false);
                                    $set('acquisition_date', null);
                                }
                                
                                // Notificación de cambio
                                $icon = $state === 'producto' ? '📦' : '🔧';
                                $msg = $state === 'producto' 
                                    ? 'Ahora puedes ingresar la cantidad de inventario' 
                                    : 'Los servicios no requieren cantidad';
                                
                                Notification::make()
                                    ->title("{$icon} Tipo cambiado")
                                    ->body($msg)
                                    ->info()
                                    ->duration(3000)
                                    ->send();
                            })
                            ->helperText('Selecciona si es un producto físico con inventario o un servicio')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Producto/Servicio')
                            ->placeholder('Ej: Laptop Dell Inspiron 15')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-cube')
                            ->autocomplete('off')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Sugerir descripción si está vacía
                                if (!empty($state)) {
                                    Notification::make()
                                        ->title('💡 Sugerencia')
                                        ->body('No olvides agregar una descripción detallada')
                                        ->info()
                                        ->duration(2000)
                                        ->send();
                                }
                            })
                            ->columnSpan(2),
                    ]),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Describe las características, especificaciones o detalles importantes...')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->helperText('Máximo 1000 caracteres'),
                ])
                ->collapsible()
                ->persistCollapsed()
                ->compact(),

            // ========== SECCIÓN INVENTARIO (solo productos) ==========
            Section::make('📊 Inventario')
                ->description('Control de existencias')
                ->icon('heroicon-o-archive-box')
                ->iconColor('success')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad a registrar')
                            ->placeholder('0')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(999999)
                            ->default(0)
                            ->prefixIcon('heroicon-o-cube-transparent')
                            ->suffix('unidades')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                self::calculateUnitPrice($set, $get);
                    
                                // // Alertas de stock
                                // if ($state <= 0) {
                                //     Notification::make()
                                //         ->title('⚠️ Sin Stock')
                                //         ->body('El producto está sin inventario')
                                //         ->warning()
                                //         ->duration(4000)
                                //         ->send();
                                // } elseif ($state < 5) {
                                //     Notification::make()
                                //         ->title('🟡 Stock Bajo')
                                //         ->body("Solo quedan {$state} unidades disponibles")
                                //         ->warning()
                                //         ->duration(4000)
                                //         ->send();
                                // } elseif ($state >= 100) {
                                //     Notification::make()
                                //         ->title('✅ Stock Alto')
                                //         ->body('Inventario óptimo para ventas')
                                //         ->success()
                                //         ->duration(3000)
                                //         ->send();
                                // }
                            })
                            ->helperText(function (Get $get) {
                                $quantity = $get('quantity') ?? 0;
                                if ($quantity <= 0) return '🔴 Sin inventario disponible';
                                if ($quantity < 5) return '🟡 Stock crítico - considera reabastecer';
                                if ($quantity < 20) return '🟠 Stock moderado';
                                return '🟢 Stock saludable';
                            })
                            ->columnSpan(1),
                        
                        Forms\Components\Placeholder::make('stock_value')
                            ->label('💰 Valor Total del Inventario')
                            ->content(function (Get $get) {
                                $quantity = $get('quantity') ?? 0;
                                $price = $get('price') ?? 0;
                                $total = (int)$quantity * (int)$price;
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'text-xl font-bold text-success-600'])
                            ->columnSpan(1),
                    ]),
                ])
                ->visible(fn (Get $get) => $get('type') === 'producto')
                ->collapsible()
                ->persistCollapsed()
                ->compact(),

            // ========== SECCIÓN PRECIOS Y COSTOS ==========
            Section::make('💵 Precios y Márgenes')
                ->description('Configura los costos y precios de venta')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('warning')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('price')
                            ->label(fn (Get $get) => $get('type') === 'producto' ? 'Costo Total de Compra' : 'Costo del Servicio')
                            ->placeholder('0')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('$')
                            ->prefixIcon('heroicon-o-banknotes')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                self::calculateUnitPrice($set, $get);
                            })
                            ->helperText(function (Get $get) {
                                if ($get('type') === 'producto') {
                                    return '💡 Precio total de compra de todas las unidades';
                                }
                                return '💡 Costo base del servicio';
                            })
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('profit_margin')
                            ->label('Margen de Ganancia')
                            ->placeholder('0')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(1000)
                            ->suffix('%')
                            ->prefixIcon('heroicon-o-arrow-trending-up')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                self::calculateUnitPrice($set, $get);
                                
                                // Feedback de margen
                                if ($state > 0) {
                                    $msg = $state < 20 ? '🟡 Margen bajo' : 
                                          ($state < 50 ? '🟢 Margen moderado' : 
                                          '💰 Margen alto');
                                    
                                    Notification::make()
                                        ->title('Margen Actualizado')
                                        ->body("{$msg}: {$state}%")
                                        ->success()
                                        ->duration(3000)
                                        ->send();
                                }
                            })
                            ->helperText('💡 Porcentaje de ganancia sobre el costo unitario')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('unit_price')
                            ->label('💲 PRECIO DE VENTA')
                            ->disabled()
                            ->numeric()
                            ->dehydrated(true)
                            ->prefix('$')
                            ->prefixIcon('heroicon-o-shopping-cart')
                            ->extraAttributes(['class' => 'text-right'])
                            ->helperText('Precio final al cliente (calculado automáticamente)')
                            ->columnSpan(1),
                    ]),

                    // ========== PANEL DE CÁLCULOS DETALLADOS ==========
                    Forms\Components\Fieldset::make('📊 Desglose de Precios')
                        ->schema([
                            Grid::make(4)->schema([
                                Forms\Components\Placeholder::make('cost_per_unit')
                                    ->label('Costo Unitario')
                                    ->content(function (Get $get) {
                                        if ($get('type') === 'producto') {
                                            $quantity = $get('quantity') ?? 1;
                                            $price = $get('price') ?? 0;
                                            $costPerUnit = (float)$quantity > 0 ? (float)$price / (float)$quantity : 0;
                                        } else {
                                            $costPerUnit = (float)$get('price') ?? 0;
                                        }
                                        return '$' . number_format($costPerUnit, 0, ',', '.');
                                    }),
                                
                                Forms\Components\Placeholder::make('profit_amount')
                                    ->label('+ Ganancia por Unidad')
                                    ->content(function (Get $get) {
                                        if ($get('type') === 'producto') {
                                            $quantity = $get('quantity') ?? 1;
                                            $price = $get('price') ?? 0;
                                            $costPerUnit = (float)$quantity > 0 ? (float)$price / (float)$quantity : 0;
                                        } else {
                                            $costPerUnit = $get('price') ?? 0;
                                        }
                                        
                                        $margin = $get('profit_margin') ?? 0;
                                        $profit = $margin > 0 ? ($costPerUnit * $margin / 100) : 0;
                                        
                                        return '$' . number_format((float)$profit, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-success-600 font-semibold']),
                                
                                Forms\Components\Placeholder::make('sale_price')
                                    ->label('= Precio Final')
                                    ->content(function (Get $get) {
                                        $unitPrice = $get('unit_price') ?? 0;
                                        return '$' . number_format((float)$unitPrice, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                                
                                Forms\Components\Placeholder::make('potential_revenue')
                                    ->label('Ingreso Potencial Total')
                                    ->content(function (Get $get) {
                                        if ($get('type') === 'producto') {
                                            $quantity = $get('quantity') ?? 0;
                                            $unitPrice = $get('unit_price') ?? 0;
                                            $total = (float)$quantity * (float)$unitPrice;
                                            return '$' . number_format($total, 0, ',', '.');
                                        }
                                        return 'N/A';
                                    })
                                    ->visible(fn (Get $get) => $get('type') === 'producto')
                                    ->extraAttributes(['class' => 'text-lg font-bold text-warning-600']),
                            ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->persistCollapsed()
                ->compact(),

            // ========== SECCIÓN REGISTRO CONTABLE (NUEVA) ==========
            Section::make('📋 Registro Contable')
                ->description('Configuración del impacto en flujo de caja')
                ->icon('heroicon-o-clipboard-document-check')
                ->iconColor('info')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_initial_inventory')
                            ->label('¿Es Inventario Inicial?')
                            ->helperText('✅ Activa si el producto ya existía antes. NO afectará el flujo de caja.')
                            ->reactive()
                            ->inline(false)
                            ->default(false)
                            ->onIcon('heroicon-o-archive-box')
                            ->offIcon('heroicon-o-shopping-cart')
                            ->onColor('success')
                            ->offColor('primary')
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    // Sugerir fecha de hoy como adquisición
                                    $set('acquisition_date', now()->toDateString());
                                    
                                    Notification::make()
                                        ->title('📦 Inventario Inicial Activado')
                                        ->body('Este producto NO se registrará en el flujo de caja')
                                        ->success()
                                        ->duration(4000)
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('🛒 Compra Nueva')
                                        ->body('Se registrará como egreso en el flujo de caja del día actual')
                                        ->warning()
                                        ->duration(4000)
                                        ->send();
                                    
                                    $set('acquisition_date', null);
                                }
                            })
                            ->columnSpan(1),
                        
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->label('Fecha de Adquisición Original')
                            ->helperText('¿Cuándo compraste originalmente este inventario?')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->default(now())
                            ->visible(fn (Get $get) => $get('is_initial_inventory') === true)
                            ->required(fn (Get $get) => $get('is_initial_inventory') === true)
                            ->prefixIcon('heroicon-o-calendar')
                            ->columnSpan(1),
                    ]),
                    
                    // Panel informativo del impacto
                    Forms\Components\Placeholder::make('flow_impact')
                        ->label('💡 Impacto en Flujo de Caja')
                        ->content(function (Get $get) {
                            $isInitial = $get('is_initial_inventory') ?? false;
                            $price = $get('price') ?? 0;
                            $type = $get('type') ?? 'producto';
                            
                            if ($type === 'servicio') {
                                return 'ℹ️ Los servicios no afectan el flujo de caja al crearlos (solo al venderlos)';
                            }
                            
                            if ($isInitial) {
                                $date = $get('acquisition_date');
                                $dateText = $date ? ' (adquirido el ' . date('d/m/Y', strtotime($date)) . ')' : '';
                                return "✅ Este producto es inventario inicial{$dateText}. NO se registrará ningún egreso.";
                            }
                            
                            return '⚠️ Se registrará un EGRESO de $' . number_format(round((float)$price, -1), 0, ',', '.') . ' en el flujo de caja de HOY';
                        })
                        ->extraAttributes(function (Get $get) {
                            $isInitial = $get('is_initial_inventory') ?? false;
                            $type = $get('type') ?? 'producto';
                            
                            if ($type === 'servicio') {
                                $class = 'text-blue-600 font-medium';
                            } else {
                                $class = $isInitial 
                                    ? 'text-success-600 font-semibold text-lg' 
                                    : 'text-warning-600 font-semibold text-lg';
                            }
                            
                            return ['class' => $class];
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn (Get $get) => $get('type') === 'producto')
                ->collapsible()
                ->collapsed(false)
                ->compact(),
        ]);
    }

    // ========== MÉTODO CENTRALIZADO DE CÁLCULO ==========
    protected static function calculateUnitPrice(Set $set, Get $get): void
    {
        $type = $get('type') ?? 'producto';
        $price = $get('price') ?? 0;
        $margin = $get('profit_margin') ?? 0;
        
        if ($type === 'producto') {
            $quantity = $get('quantity') ?? 1;
            $costPerUnit = (int)$quantity > 0 ? (int)$price / (int)$quantity : 0;
            $profit = $margin > 0 ? ($costPerUnit * $margin / 100) : 0;
            $unitPrice = round($costPerUnit + $profit, 2);
        } else {
            $profit = $margin > 0 ? ((int)$price * $margin / 100) : 0;
            $unitPrice = round((int)$price + $profit, 2);
        }
        
        $set('unit_price', $unitPrice);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto/Servicio')
                    ->searchable()
                    ->sortable()
                    ->icon(fn ($record) => $record->type === 'producto' ? 'heroicon-o-cube' : 'heroicon-o-wrench-screwdriver')
                    ->description(fn ($record) => $record->description)
                    ->wrap()
                    ->limit(50),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->sortable()
                    ->colors([
                        'primary' => 'producto',
                        'warning' => 'servicio',
                    ])
                    ->icons([
                        'heroicon-o-cube' => 'producto',
                        'heroicon-o-wrench-screwdriver' => 'servicio',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'producto' ? 'Producto' : 'Servicio'),
                
                // ========== NUEVA COLUMNA: ORIGEN ==========
                Tables\Columns\BadgeColumn::make('is_initial_inventory')
                    ->label('Origen')
                    ->formatStateUsing(fn ($state) => $state ? 'Inventario Inicial' : 'Compra Nueva')
                    ->colors([
                        'success' => true,
                        'primary' => false,
                    ])
                    ->icons([
                        'heroicon-o-archive-box' => true,
                        'heroicon-o-shopping-cart' => false,
                    ])
                    ->visible(fn ($record) => $record?->type === 'producto')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state <= 0 => 'danger',
                        $state < 5 => 'warning',
                        $state < 20 => 'info',
                        default => 'success'
                    })
                    ->icon(fn ($state) => match(true) {
                        $state <= 0 => 'heroicon-o-x-circle',
                        $state < 5 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-check-circle'
                    })
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->type === 'producto' 
                            ? "{$state} unid." 
                            : 'N/A'
                    )
                    ->visible(fn ($livewire) => !$livewire->getTableFilterState('type') || $livewire->getTableFilterState('type')['value'] !== 'servicio'),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Costo')
                    ->money('COP')
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->type === 'producto' 
                            ? 'Costo total de compra' 
                            : 'Costo del servicio'
                    )
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Margen')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state <= 0 => 'gray',
                        $state < 20 => 'warning',
                        $state < 50 => 'success',
                        default => 'primary'
                    })
                    ->description('Ganancia configurada')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('💲 Precio Venta')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description('Precio al cliente'),
                
                Tables\Columns\TextColumn::make('potential_revenue')
                    ->label('Valor Inventario')
                    ->money('COP')
                    ->getStateUsing(fn ($record) => 
                        $record->type === 'producto' 
                            ? $record->quantity * $record->unit_price 
                            : 0
                    )
                    ->color('warning')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn ($livewire) => !$livewire->getTableFilterState('type') || $livewire->getTableFilterState('type')['value'] !== 'servicio')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                // ========== NUEVA COLUMNA: FECHA ADQUISICIÓN ==========
                Tables\Columns\TextColumn::make('acquisition_date')
                    ->label('Fecha Adquisición')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn ($record) => $record->acquisition_date?->diffForHumans())
                    ->visible(fn ($record) => $record?->is_initial_inventory)
                    ->icon('heroicon-o-calendar')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'producto' => '📦 Productos',
                        'servicio' => '🔧 Servicios',
                    ])
                    ->native(false),
                
                // ========== NUEVOS FILTROS ==========
                Tables\Filters\Filter::make('initial_inventory')
                    ->label('Solo Inventario Inicial')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_initial_inventory', true))
                    ->indicateUsing(fn () => ['📦 Solo inventario inicial']),
                
                Tables\Filters\Filter::make('new_purchases')
                    ->label('Solo Compras Nuevas')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_initial_inventory', false))
                    ->indicateUsing(fn () => ['🛒 Solo compras nuevas registradas en flujo de caja']),
                
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->toggle()
                    ->query(fn ($query) => $query->where('type', 'producto')->where('quantity', '<', 5))
                    ->indicateUsing(fn () => ['⚠️ Solo productos con stock crítico']),
                
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sin Stock')
                    ->toggle()
                    ->query(fn ($query) => $query->where('type', 'producto')->where('quantity', '<=', 0))
                    ->indicateUsing(fn () => ['❌ Solo productos sin inventario']),
                
                Tables\Filters\Filter::make('high_margin')
                    ->label('Alto Margen (>50%)')
                    ->toggle()
                    ->query(fn ($query) => $query->where('profit_margin', '>=', 50))
                    ->indicateUsing(fn () => ['💰 Solo productos con margen alto']),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\Action::make('adjust_stock')
                        ->label('Ajustar Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn ($record) => $record->type === 'producto')
                        ->form([
                            Forms\Components\Radio::make('action')
                                ->label('Acción')
                                ->options([
                                    'add' => 'Agregar inventario',
                                    'subtract' => 'Reducir inventario',
                                    'set' => 'Establecer cantidad exacta',
                                ])
                                ->default('add')
                                ->required()
                                ->reactive(),
                            
                            Forms\Components\TextInput::make('amount')
                                ->label(fn (Get $get) => 
                                    $get('action') === 'set' 
                                        ? 'Nueva cantidad' 
                                        : 'Cantidad a ' . ($get('action') === 'add' ? 'agregar' : 'reducir')
                                )
                                ->numeric()
                                ->required()
                                ->minValue(0),
                            
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->placeholder('Ej: Compra de inventario, devolución, ajuste, etc.')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $newQuantity = match($data['action']) {
                                'add' => $record->quantity + $data['amount'],
                                'subtract' => max(0, $record->quantity - $data['amount']),
                                'set' => $data['amount'],
                            };
                            
                            $record->update(['quantity' => $newQuantity]);
                            
                            Notification::make()
                                ->title('✅ Stock Actualizado')
                                ->body("Nuevo stock de {$record->name}: {$newQuantity} unidades")
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->action(function ($record) {
                            $newProduct = $record->replicate();
                            $newProduct->name = $record->name . ' (Copia)';
                            $newProduct->is_initial_inventory = false; // La copia será compra nueva
                            $newProduct->acquisition_date = null;
                            $newProduct->save();
                            
                            Notification::make()
                                ->title('✅ Producto duplicado')
                                ->body('La copia se marcó como "Compra Nueva"')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    
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
                    
                    Tables\Actions\BulkAction::make('update_margin')
                        ->label('Actualizar Margen')
                        ->icon('heroicon-o-calculator')
                        ->form([
                            Forms\Components\TextInput::make('new_margin')
                                ->label('Nuevo margen de ganancia (%)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->suffix('%'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['profit_margin' => $data['new_margin']]);
                            }
                            
                            Notification::make()
                                ->title('✅ Márgenes actualizados')
                                ->body(count($records) . ' productos modificados')
                                ->success()
                                ->send();
                        }),
                    
                    // ========== NUEVA ACCIÓN MASIVA ==========
                    Tables\Actions\BulkAction::make('mark_as_initial')
                        ->label('Marcar como Inventario Inicial')
                        ->icon('heroicon-o-archive-box')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Convertir a Inventario Inicial')
                        ->modalDescription('¿Estás seguro? Esto marcará los productos como inventario inicial. No afectará registros existentes en flujo de caja.')
                        ->form([
                            Forms\Components\DatePicker::make('acquisition_date')
                                ->label('Fecha de adquisición')
                                ->default(now())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->type === 'producto') {
                                    $record->update([
                                        'is_initial_inventory' => true,
                                        'acquisition_date' => $data['acquisition_date'],
                                    ]);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('✅ Inventario Inicial Actualizado')
                                ->body("{$count} productos marcados como inventario inicial")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $lowStock = static::getModel()::where('type', 'producto')
            ->where('quantity', '<', 5)
            ->count();
        
        return $lowStock > 0 ? (string) $lowStock : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $lowStock = static::getModel()::where('type', 'producto')
            ->where('quantity', '<', 5)
            ->count();
        
        return $lowStock > 10 ? 'danger' : ($lowStock > 0 ? 'warning' : null);
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Productos con stock bajo';
    }
}