<?php

namespace App\Filament\App\Resources;

use App\Models\Sale;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\App\Resources\SaleResource\Pages;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationBadgeTooltip = 'Ventas de hoy';
    
    public static function getModelLabel(): string
    {
        return 'Venta';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Ventas';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ========== SECCIÓN CLIENTE MEJORADA ==========
            Section::make('👤 Información del Cliente')
                ->description('Datos de contacto y tipo de cliente')
                ->icon('heroicon-o-user-circle')
                ->iconColor('primary')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nombre Completo')
                            ->placeholder('Juan Pérez García')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user')
                            ->autocomplete('name')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Teléfono')
                            ->placeholder('300 123 4567')
                            ->tel()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-phone')
                            ->mask('999 999 9999')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Correo Electrónico')
                            ->placeholder('cliente@ejemplo.com')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope')
                            ->autocomplete('email')
                            ->columnSpan(1),
                    ]),
                    
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('customer_type')
                            ->label('Tipo de Cliente')
                            ->options([
                                'regular' => '👤 Regular',
                                'wholesale' => '🏢 Mayorista',
                                'vip' => '⭐ VIP',
                            ])
                            ->default('regular')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-user-group')
                            ->searchable()
                            ->helperText('Los clientes VIP y Mayoristas pueden tener descuentos especiales'),
                        
                    ]),
                ])
                ->collapsible()
                ->persistCollapsed()
                ->compact(),

            // ========== SECCIÓN PRODUCTOS MEJORADA ==========
            Section::make('🛍️ Productos y Servicios')
                ->description('Agrega los productos que desea adquirir el cliente')
                ->icon('heroicon-o-shopping-bag')
                ->iconColor('success')
                ->schema([
                    Forms\Components\Repeater::make('sale_details')
                        ->relationship('saleDetails')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Producto / Servicio')
                                ->options(function () {
                                    return Product::query()
                                        ->where(function($query) {
                                            $query->where('quantity', '>', 0)
                                                  ->orWhere('type', 'servicio');
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($product) {
                                            $icon = $product->type === 'servicio' ? '🔧' : '📦';
                                            $stockInfo = '';
                                            
                                            if ($product->type === 'producto') {
                                                $stockColor = $product->quantity < 5 ? '🔴' : 
                                                             ($product->quantity < 20 ? '🟡' : '🟢');
                                                $stockInfo = " {$stockColor} {$product->quantity} unid.";
                                            } else {
                                                $stockInfo = " ✓ Disponible";
                                            }
                                            
                                            $price = " • $" . number_format($product->unit_price, 0, ',', '.');
                                            
                                            return [$product->id => "{$icon} {$product->name}{$stockInfo}{$price}"];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                    $product = Product::find($state);
                                    if (!$product) return;
                                    
                                    $unitPrice = $product->unit_price;
                                    $set('unit_price', $unitPrice);
                                    
                                    $quantity = $get('quantity') ?? 1;
                                    $set('total', $quantity * $unitPrice);
                                    
                                    self::updateTotals($livewire);
                                    
                                    // Notificaciones mejoradas
                                    if ($product->type === 'producto') {
                                        if ($product->quantity < 5 && $product->quantity > 0) {
                                            Notification::make()
                                                ->title('⚠️ Stock Crítico')
                                                ->body("Solo quedan **{$product->quantity} unidades** de {$product->name}")
                                                ->warning()
                                                ->duration(5000)
                                                ->send();
                                        } elseif ($product->quantity >= 20) {
                                            Notification::make()
                                                ->title('✅ Producto Agregado')
                                                ->body("{$product->name} - Stock suficiente ({$product->quantity} unid.)")
                                                ->success()
                                                ->duration(3000)
                                                ->send();
                                        }
                                    }
                                })
                                ->columnSpan(2),
                            
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cant.')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(9999)
                                ->reactive()
                                ->suffixIcon('heroicon-o-calculator')
                                ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                    $productId = $get('product_id');
                                    $unitPrice = $get('unit_price') ?? 0;
                                    
                                    if ($productId) {
                                        $saleDetails = data_get($livewire, 'data.sale_details', []);
                                        $currentIndex = $get('__index');
                                        
                                        $totalRequested = (int)$state;
                                        foreach ($saleDetails as $index => $detail) {
                                            if ($index != $currentIndex && ($detail['product_id'] ?? null) === $productId) {
                                                $totalRequested += (int) ($detail['quantity'] ?? 0);
                                            }
                                        }
                                        
                                        $product = Product::find($productId);
                                        if ($product && $product->type === 'producto') {
                                            if ($totalRequested > $product->quantity) {
                                                $set('quantity', $product->quantity);
                                                
                                                Notification::make()
                                                    ->title('❌ Stock Insuficiente')
                                                    ->body("Cantidad ajustada a **{$product->quantity}** unidades disponibles de {$product->name}")
                                                    ->danger()
                                                    ->persistent()
                                                    ->send();
                                                return;
                                            }
                                            
                                            $percentage = ($totalRequested / $product->quantity) * 100;
                                            if ($percentage >= 80) {
                                                Notification::make()
                                                    ->title('⚡ Alto Consumo de Stock')
                                                    ->body("Estás usando el **" . round($percentage) . "%** del inventario de {$product->name}")
                                                    ->warning()
                                                    ->duration(5000)
                                                    ->send();
                                            }
                                        }
                                    }
                                    
                                    $set('total', $state * $unitPrice);
                                    self::updateTotals($livewire);
                                })
                                ->helperText(function (Get $get) {
                                    $productId = $get('product_id');
                                    if (!$productId) return null;
                                    
                                    $product = Product::find($productId);
                                    if (!$product || $product->type !== 'producto') return null;
                                    
                                    $icon = $product->quantity < 5 ? '🔴' : ($product->quantity < 20 ? '🟡' : '🟢');
                                    return "{$icon} Disponible: **{$product->quantity}** unidades";
                                })
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unit.')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true)
                                ->required()
                                ->prefix('$')
                                ->suffixIcon('heroicon-o-currency-dollar')
                                ->extraInputAttributes(['class' => 'text-right'])
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('total')
                                ->label('Subtotal')
                                ->disabled()
                                ->dehydrated(true)
                                ->numeric()
                                ->prefix('$')
                                ->suffixIcon('heroicon-o-calculator')
                                ->extraInputAttributes(['class' => 'text-right font-semibold text-success-600'])
                                ->columnSpan(1),
                        ])
                        ->columns(5)
                        ->createItemButtonLabel('➕ Agregar Producto')
                        ->addActionLabel('Agregar otro producto')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(function (array $state): ?string {
                            if (!isset($state['product_id'])) {
                                return '🆕 Nuevo producto';
                            }
                            $product = Product::find($state['product_id']);
                            $qty = $state['quantity'] ?? 1;
                            $total = $state['total'] ?? 0;
                            return $product ? "📦 {$product->name} x{$qty} = $" . number_format($total, 0, ',', '.') : '❓ Producto';
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $totalGeneral = array_reduce($state, fn($carry, $item) => $carry + ($item['total'] ?? 0), 0);
                            $set('subtotal', $totalGeneral);
                            $discount = 0; // Recalcular con descuento si existe
                            $set('total_amount', $totalGeneral - $discount);
                        })
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action
                                ->requiresConfirmation()
                                ->modalHeading('Eliminar producto')
                                ->modalDescription('¿Estás seguro de eliminar este producto de la venta?')
                                ->modalSubmitActionLabel('Sí, eliminar')
                        )
                        ->minItems(1)
                        ->defaultItems(1),
                ])
                ->collapsible()
                ->persistCollapsed()
                ->compact(),

            // ========== SECCIÓN RESUMEN MEJORADA ==========
            Section::make('💰 Resumen y Pago')
                ->description('Totales, descuentos y forma de pago')
                ->icon('heroicon-o-banknotes')
                ->iconColor('warning')
                ->schema([
                    // Subtotales y descuentos
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->disabled()
                            ->dehydrated(true)
                            ->numeric()
                            ->prefix('$')
                            ->suffixIcon('heroicon-o-calculator')
                            ->default(0)
                            ->extraInputAttributes(['class' => 'text-right text-lg'])
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('discount_type')
                            ->label('Tipo Descuento')
                            ->options([
                                'fixed' => '💵 Valor Fijo',
                                'percentage' => '📊 Porcentaje %',
                            ])
                            ->default('fixed')
                            ->dehydrated(false)
                            ->native(false)
                            ->reactive()
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('discount')
                            ->label(fn (Get $get) => $get('discount_type') === 'percentage' ? 'Descuento (%)' : 'Descuento ($)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => $get('discount_type') === 'percentage' ? 100 : 999999999)
                            ->prefix(fn (Get $get) => $get('discount_type') === 'percentage' ? '' : '$')
                            ->suffix(fn (Get $get) => $get('discount_type') === 'percentage' ? '%' : '')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $subtotal = $get('subtotal') ?? 0;
                                $discountType = $get('discount_type') ?? 'fixed';
                                $discount = $state ?? 0;
                                
                                $discountAmount = $discountType === 'percentage' 
                                    ? ($subtotal * $discount / 100) 
                                    : $discount;
                                
                                $set('total_amount', max(0, $subtotal - $discountAmount));
                            })
                            ->helperText(function (Get $get) {
                                $subtotal = $get('subtotal') ?? 0;
                                $discountType = $get('discount_type') ?? 'fixed';
                                $discount = $get('discount') ?? 0;
                                
                                if ($discountType === 'percentage' && $discount > 0) {
                                    $amount = $subtotal * $discount / 100;
                                    return "≈ $" . number_format($amount, 0, ',', '.');
                                }
                                return null;
                            })
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('total_amount')
                            ->label('💳 TOTAL A PAGAR')
                            ->disabled()
                            ->dehydrated(true)
                            ->numeric()
                            ->prefix('$')
                            ->suffixIcon('heroicon-o-currency-dollar')
                            ->default(0)
                            ->extraInputAttributes(['class' => 'text-right text-xl font-bold text-primary-600'])
                            ->columnSpan(1),
                    ]),
                    
                    // Forms\Components\Separator::make(),

                    // Método de pago
                    Grid::make(3)->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'cash' => '💵 Efectivo',
                                'card' => '💳 Tarjeta Débito/Crédito',
                                'transfer' => '🏦 Transferencia Bancaria',
                                'credit' => '📋 Crédito (A plazo)',
                            ])
                            ->default('cash')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Auto-completar estado según método
                                if ($state === 'credit') {
                                    $set('payment_status', 'pending');
                                } else {
                                    $set('payment_status', 'paid');
                                }
                            })
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('payment_status')
                            ->label('Estado del Pago')
                            ->options([
                                'paid' => '✅ Pagado',
                                'pending' => '⏳ Pendiente',
                                'partial' => '🔄 Pago Parcial',
                            ])
                            ->default('paid')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Monto Recibido')
                            ->numeric()
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('payment_method') === 'cash')
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $total = $get('total_amount') ?? 0;
                                $change = max(0, ($state ?? 0) - $total);
                                $set('change', $change);
                            })
                            ->helperText('Ingresa el monto entregado por el cliente')
                            ->columnSpan(1),
                    ]),
                    
                    Forms\Components\TextInput::make('change')
                        ->label('💵 Cambio a Devolver')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->visible(fn (Get $get) => $get('payment_method') === 'cash' && ($get('change') ?? 0) > 0)
                        ->extraInputAttributes(['class' => 'text-right text-lg font-bold text-success-600']),

                    Forms\Components\Textarea::make('notes')
                        ->label('📝 Observaciones')
                        ->placeholder('Notas adicionales sobre la venta, entregas pendientes, etc.')
                        ->rows(2)
                        ->columnSpanFull()
                        ->maxLength(500),
                ])
                ->collapsible()
                ->persistCollapsed()
                ->compact(),
        ]);
    }

    // Método auxiliar para actualizar totales
    protected static function updateTotals($livewire): void
    {
        $saleDetails = data_get($livewire, 'data.sale_details', []);
        $subtotal = array_reduce($saleDetails, fn($carry, $item) => $carry + ($item['total'] ?? 0), 0);
        
        $discountType = data_get($livewire, 'data.discount_type', 'fixed');
        $discount = data_get($livewire, 'data.discount', 0);
        
        $discountAmount = $discountType === 'percentage' 
            ? ($subtotal * $discount / 100) 
            : $discount;
        
        data_set($livewire, 'data.subtotal', $subtotal);
        data_set($livewire, 'data.total_amount', max(0, $subtotal - $discountAmount));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->getStateUsing(fn ($record) => $record->customer_name ?? 'Sin registro')
                    ->description(fn ($record) => $record->customer_phone ?? '')
                    ->wrap(),
                
                Tables\Columns\BadgeColumn::make('customer_type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'regular',
                        'success' => 'wholesale',
                        'warning' => 'vip',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'regular',
                        'heroicon-o-building-office' => 'wholesale',
                        'heroicon-o-star' => 'vip',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'regular' => 'Regular',
                        'wholesale' => 'Mayorista',
                        'vip' => 'VIP',
                        default => 'Regular'
                    }),
                
                Tables\Columns\TextColumn::make('saleDetails.quantity')
                    ->label('Items')
                    ->formatStateUsing(function ($state, $record) {
                        $total = $record->saleDetails->sum('quantity');
                        $unique = $record->saleDetails->count();
                        return "{$total} unid. ({$unique} productos)";
                    })
                    ->icon('heroicon-o-shopping-bag')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Método')
                    ->icons([
                        'heroicon-o-banknotes' => 'cash',
                        'heroicon-o-credit-card' => 'card',
                        'heroicon-o-building-library' => 'transfer',
                        'heroicon-o-document-text' => 'credit',
                    ])
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'card',
                        'info' => 'transfer',
                        'warning' => 'credit',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'credit' => 'Crédito',
                        default => 'Efectivo'
                    }),
                
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'info' => 'partial',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'paid',
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-arrow-path' => 'partial',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'paid' => 'Pagado',
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        default => 'Pagado'
                    }),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->description(fn ($record) => $record->discount > 0 ? "Dto: $" . number_format($record->discount, 0) : null),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipo Cliente')
                    ->options([
                        'regular' => '👤 Regular',
                        'wholesale' => '🏢 Mayorista',
                        'vip' => '⭐ VIP',
                    ])
                    ->native(false)
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método Pago')
                    ->options([
                        'cash' => '💵 Efectivo',
                        'card' => '💳 Tarjeta',
                        'transfer' => '🏦 Transferencia',
                        'credit' => '📋 Crédito',
                    ])
                    ->native(false)
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado Pago')
                    ->options([
                        'paid' => '✅ Pagado',
                        'pending' => '⏳ Pendiente',
                        'partial' => '🔄 Parcial',
                    ])
                    ->native(false)
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                

                
                Tables\Actions\ActionGroup::make([                    
                    // Acción: Descargar PDF
                    Tables\Actions\Action::make('download_receipt')
                        ->label('Descargar PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn ($record) => route('sales.receipt.pdf', $record->id))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    
                    
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
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar venta permanentemente')
                        ->modalDescription('Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar'),
                ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Exportar selección')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Implementar exportación
                            Notification::make()
                                ->title('Exportación en proceso')
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
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? (string) $todayCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 10 ? 'success' : ($todayCount > 5 ? 'warning' : 'primary');
    }
}