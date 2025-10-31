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
                    ->description('Datos principales de la entidad')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre de la Entidad')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-building-office-2')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                    if (!$get('id')) {
                                        // Auto-generar el dominio basado en el nombre
                                        $slug = Str::slug($state);
                                        $set('domain', $slug);
                                    }
                                })
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('domain')
                                ->label('Dominio')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Se generará automáticamente como: nombre-entidad.cuentas.duckdns.org')
                                ->placeholder('papeleria-mila.cuentas.duckdns.org')
                                ->alphaDash()
                                ->suffixIcon('heroicon-o-globe-alt')
                                ->live(onBlur: true)
                                ->columnSpan(1),
                        ]),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-envelope')
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-phone')
                                ->placeholder('300 123 4567')
                                ->columnSpan(1),
                        ]),
                    ])->columns(1),

                Forms\Components\Section::make('Información Fiscal y Comercial')
                    ->description('Datos legales y comerciales de la entidad')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nit')
                                ->label('NIT / Documento')
                                ->placeholder('900.123.456-7')
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-identification')
                                ->helperText('Número de identificación tributaria')
                                ->columnSpan(1),
                            
                            Forms\Components\Textarea::make('address')
                                ->label('Dirección Fiscal')
                                ->placeholder('Calle 123 # 45-67, Oficina 890')
                                ->rows(2)
                                ->maxLength(500)
                                ->helperText('Dirección completa de la entidad')
                                ->columnSpan(1),
                        ]),
                        
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo de la Empresa')
                            ->image()
                            ->disk('public')
                            ->directory('tenant-logos')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '16:9',
                                '4:3',
                            ])
                            ->maxSize(2048)
                            ->helperText('Logo de la empresa (máximo 2MB, formatos: JPG, PNG)')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->imagePreviewHeight('150')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Configuración del Sistema')
                    ->description('Configuración y límites de la entidad')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Estado')
                                ->options([
                                    'active' => 'Activo',
                                    'suspended' => 'Suspendido',
                                    'trial' => 'Prueba',
                                ])
                                ->required()
                                ->default('active')
                                ->native(false)
                                ->live()
                                ->prefixIcon('heroicon-o-signal')
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('max_users')
                                ->label('Máximo de Usuarios')
                                ->numeric()
                                ->default(10)
                                ->required()
                                ->minValue(1)
                                ->prefixIcon('heroicon-o-users')
                                ->suffix('usuarios')
                                ->columnSpan(1),
                        ]),
                        
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Razón de Suspensión')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'suspended')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Ingrese la razón de la suspensión...'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-logo.png'))
                    ->size(50)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Tenant $record): string => $record->nit ? "NIT: {$record->nit}" : ''),
                
                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Dominio')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Dominio copiado')
                    ->icon('heroicon-o-globe-alt')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state, Tenant $record) {
                        return $record->domains->first()?->domain ?? 'Sin dominio';
                    })
                    ->url(function (Tenant $record) {
                        $domain = $record->domains->first()?->domain;
                        return $domain ? 'http://' . $domain . '/app' : null;
                    })
                    ->openUrlInNewTab(),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable()
                    ->wrap()
                    ->limit(30)
                    ->tooltip(fn (Tenant $record): ?string => $record->address)
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'trial',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-x-circle' => 'suspended',
                        'heroicon-o-clock' => 'trial',
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
                    ->icon('heroicon-o-users')
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
                    ])
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('logo')
                    ->label('Con Logo')
                    ->placeholder('Todos')
                    ->trueLabel('Con logo')
                    ->falseLabel('Sin logo')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('logo'),
                        false: fn ($query) => $query->whereNull('logo'),
                    ),
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
            'index' => Pages\ListTenant::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}