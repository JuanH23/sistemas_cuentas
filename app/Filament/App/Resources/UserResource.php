<?php

namespace App\Filament\App\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\App\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';
    public static function getModelLabel(): string
    {
        return 'Usuario';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Usuarios';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Acción para restaurar si el registro está soft deleted
                Tables\Actions\Action::make('restore')
                    ->label('Restaurar')
                    ->action(fn ($record) => $record->restore())
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation(),
                // Acción para eliminar definitivamente si el registro ya fue soft deleted
                Tables\Actions\Action::make('forceDelete')
                    ->label('Eliminar Definitivamente')
                    ->action(fn ($record) => $record->forceDelete())
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation(),
                // Acción de borrado normal solo se muestra si el registro NO está soft deleted
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
