<?php

namespace App\Filament\App\Resources;

use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\App\Resources\ProviderResource\Pages;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Administración';

    public static function getModelLabel(): string
    {
        return 'Proveedor';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Proveedores';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->maxLength(50),
                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(), // Permite filtrar registros eliminados (soft deletes)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Acción para restaurar registros eliminados
                Tables\Actions\Action::make('restore')
                    ->label('Restaurar')
                    ->action(fn ($record) => $record->restore())
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation(),
                // Acción para eliminar definitivamente registros eliminados
                Tables\Actions\Action::make('forceDelete')
                    ->label('Eliminar definitivamente')
                    ->action(fn ($record) => $record->forceDelete())
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation(),
                // Acción para borrar normalmente solo si el registro no está eliminado
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Puedes agregar aquí tus Relation Managers si lo requieres
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit'   => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
