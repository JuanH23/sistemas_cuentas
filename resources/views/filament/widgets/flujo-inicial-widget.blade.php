<x-filament-widgets::widget>
    <x-filament::section
        :heading="'🚀 Configuración Inicial del Negocio'"
        description="Registra el capital inicial con el que comienza tu negocio"
    >
        <form wire:submit="guardar" class="space-y-4">
            {{ $this->form }}

            <div class="flex justify-end mt-4">
                <x-filament::button type="submit" size="lg">
                    Guardar Capital Inicial
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>