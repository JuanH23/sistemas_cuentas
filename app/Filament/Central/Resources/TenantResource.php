<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Gestión de Tenants';
    
    protected static ?string $navigationLabel = 'Entidades';
    
    protected static ?string $modelLabel = 'Entidad';
    
    protected static ?string $pluralModelLabel = 'Entidades';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Entidad')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if (!$get('id')) {
                                    // Auto-generar el dominio basado en el nombre
                                    $slug = Str::slug($state);
                                    $set('domain', $slug);
                                }
                            }),
                        
                        Forms\Components\TextInput::make('domain')
                            ->label('Dominio')
                            ->required()
                            ->unique(table: 'domains', column: 'domain', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Se generará automáticamente como: nombre-entidad.sistema_cuentas_2.test')
                            ->placeholder('papeleria-mila.sistema_cuentas_2.test')
                            ->alphaDash()
                            ->suffixIcon('heroicon-o-globe-alt')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                // Si el usuario modifica el dominio manualmente, validar formato
                                if ($state && !str_contains($state, '.')) {
                                    $set('domain', $state . '.sistema_cuentas_2.test');
                                }
                            }),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'suspended' => 'Suspendido',
                                'trial' => 'Prueba',
                            ])
                            ->required()
                            ->default('active')
                            ->live(),
                        
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Razón de Suspensión')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'suspended')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('max_users')
                            ->label('Máximo de Usuarios')
                            ->numeric()
                            ->default(10)
                            ->required()
                            ->minValue(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Dominio')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Dominio copiado')
                    ->icon('heroicon-o-globe-alt')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state, Tenant $record) {
                        // Obtener el primer dominio
                        return $record->domains->first()?->domain ?? 'Sin dominio';
                    })
                    ->url(function (Tenant $record) {
                        $domain = $record->domains->first()?->domain;
                        return $domain ? 'http://' . $domain . '/app' : null;
                    })
                    ->openUrlInNewTab(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'trial',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'trial' => 'Prueba',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('max_users')
                    ->label('Límite Usuarios')
                    ->badge()
                    ->color('gray')
                    ->suffix(' usuarios'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'trial' => 'Prueba',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('visit')
                        ->label('Abrir Sitio')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(function (Tenant $record) {
                            $domain = $record->domains->first()?->domain;
                            return $domain ? 'http://' . $domain . '/app' : null;
                        })
                        ->openUrlInNewTab()
                        ->color('success')
                        ->visible(fn (Tenant $record) => $record->domains->isNotEmpty()),
                    
                    Tables\Actions\Action::make('migrate')
                        ->label('Migrar BD')
                        ->icon('heroicon-o-circle-stack')
                        ->action(function (Tenant $record) {
                            \Artisan::call('tenants:migrate', [
                                '--tenants' => [$record->id],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->modalDescription('Se ejecutarán las migraciones pendientes en la base de datos del tenant.')
                        ->successNotificationTitle('Migraciones ejecutadas correctamente')
                        ->color('info'),
                    
                    Tables\Actions\Action::make('seed')
                        ->label('Sembrar Datos')
                        ->icon('heroicon-o-beaker')
                        ->action(function (Tenant $record) {
                            \Artisan::call('tenants:seed', [
                                '--tenants' => [$record->id],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('Datos sembrados correctamente')
                        ->color('warning'),
                    
                    Tables\Actions\Action::make('suspend')
                        ->label('Suspender')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->visible(fn (Tenant $record) => $record->status !== 'suspended')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Razón de Suspensión')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Tenant $record, array $data) {
                            $record->update([
                                'status' => 'suspended',
                                'suspension_reason' => $data['reason'],
                            ]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Suspender Entidad')
                        ->modalDescription('¿Está seguro que desea suspender esta entidad?')
                        ->successNotificationTitle('Entidad suspendida'),
                    
                    Tables\Actions\Action::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->visible(fn (Tenant $record) => $record->status === 'suspended')
                        ->action(function (Tenant $record) {
                            $record->update([
                                'status' => 'active',
                                'suspension_reason' => null,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Activar Entidad')
                        ->successNotificationTitle('Entidad activada'),
                    
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Entidad')
                        ->modalDescription('¿Está seguro? Esta acción eliminará la entidad y su base de datos.'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}