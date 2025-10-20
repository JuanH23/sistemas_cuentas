<?php

namespace App\Filament\App\Widgets;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use App\Models\CashFlow;

class FlujoInicialWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.flujo-inicial-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('initial_balance')
                    ->label('Capital Inicial del Negocio')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->minValue(0)
                    ->helperText('💰 Ingresa el monto con el que inicias tu negocio'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->default('Capital inicial del negocio')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $data = $this->form->getState();
        
        // Verificar que no exista ningún registro previo
        $existe = CashFlow::where('user_id', auth()->id())->exists();
        
        if ($existe) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('El capital inicial ya fue registrado')
                ->send();
            return;
        }

        CashFlow::create([
            'user_id' => auth()->id(),
            'date' => now(),
            'initial_balance' => $data['initial_balance'],
            'final_balance' => $data['initial_balance'],
            'notes' => $data['notes'],
        ]);

        Notification::make()
            ->success()
            ->title('¡Capital inicial registrado!')
            ->body('Tu negocio comienza con $' . number_format($data['initial_balance'], 2))
            ->send();

        // Esto recarga automáticamente la página
        $this->dispatch('refresh');
    }

    public static function canView(): bool
    {
        // Solo mostrar si NO hay registros
        return CashFlow::where('user_id', auth()->id())->count() === 0;
    }
}