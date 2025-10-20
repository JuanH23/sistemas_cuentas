{{-- resources/views/filament/widgets/cerrar-caja.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section
        :heading="'💰 Cerrar Caja - ' . ($this->cashFlowDate ?? now()->format('d/m/Y'))"
        :description="$this->hasCashFlow ? ($this->alreadyClosed ? '✅ Caja ya cerrada' : '🔓 Caja abierta') : '⚠️ No hay caja activa'"
    >
        @if (!$this->hasCashFlow)
            {{-- No hay caja activa --}}
            <div class="text-center py-12">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-warning-100 dark:bg-warning-900/20 mb-4">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="h-8 w-8 text-warning-600 dark:text-warning-400"
                    />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    No hay caja activa
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No se encontró una apertura de caja para el día de hoy.
                </p>
            </div>

        @elseif ($this->alreadyClosed)
            {{-- Caja ya cerrada --}}
            <div class="text-center py-12">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-success-100 dark:bg-success-900/20 mb-4">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-8 w-8 text-success-600 dark:text-success-400"
                    />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Caja cerrada
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ya existe un cierre de caja para el día de hoy.
                </p>
            </div>

        @else
            {{-- Resumen del día --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Ingresos --}}
                <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-success-600 dark:text-success-400">
                                Ingresos
                            </p>
                            <p class="text-2xl font-bold text-success-900 dark:text-success-100">
                                ${{ number_format($this->totalIncome ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-success-600 dark:text-success-400 mt-1">
                                {{ $this->transactionCount ?? 0 }} movimientos
                            </p>
                        </div>
                        <x-filament::icon
                            icon="heroicon-o-arrow-trending-up"
                            class="h-8 w-8 text-success-400"
                        />
                    </div>
                </div>

                {{-- Egresos --}}
                <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-danger-600 dark:text-danger-400">
                                Egresos
                            </p>
                            <p class="text-2xl font-bold text-danger-900 dark:text-danger-100">
                                ${{ number_format($this->totalExpense ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <x-filament::icon
                            icon="heroicon-o-arrow-trending-down"
                            class="h-8 w-8 text-danger-400"
                        />
                    </div>
                </div>

                {{-- Saldo Esperado --}}
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                Saldo Esperado
                            </p>
                            <p class="text-2xl font-bold text-primary-900 dark:text-primary-100">
                                ${{ number_format($this->expectedBalance ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <x-filament::icon
                            icon="heroicon-o-calculator"
                            class="h-8 w-8 text-primary-400"
                        />
                    </div>
                </div>
            </div>

            @if (!$this->showConfirmation)
                {{-- Botón para abrir modal --}}
                <div class="flex justify-end">
                    <x-filament::button
                        wire:click="prepararCierre"
                        color="primary"
                        size="lg"
                        icon="heroicon-o-lock-closed"
                    >
                        Cerrar Caja
                    </x-filament::button>
                </div>
            @else
                {{-- Modal de confirmación --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Confirmar cierre de caja
                    </h3>

                    {{-- Input saldo real --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Saldo Real (contado en caja)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 text-lg">
                                $
                            </span>
                            <input
                                type="number"
                                wire:model.live="realBalance"
                                step="0.01"
                                class="block w-full pl-8 pr-12 py-3 text-2xl font-bold border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                placeholder="0.00"
                            />
                        </div>

                        {{-- Mostrar diferencia --}}
                        @php
                            $difference = $this->getDifference();
                        @endphp

                        @if ($difference != 0)
                            <div class="mt-3 p-4 rounded-lg {{ $difference > 0 ? 'bg-warning-50 dark:bg-warning-900/20' : 'bg-danger-50 dark:bg-danger-900/20' }}">
                                <div class="flex items-center">
                                    <x-filament::icon
                                        icon="{{ $difference > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-exclamation-circle' }}"
                                        class="h-5 w-5 {{ $difference > 0 ? 'text-warning-600' : 'text-danger-600' }} mr-2"
                                    />
                                    <div>
                                        <p class="text-sm font-semibold {{ $difference > 0 ? 'text-warning-800 dark:text-warning-200' : 'text-danger-800 dark:text-danger-200' }}">
                                            {{ $difference > 0 ? '💰 Sobrante' : '⚠️ Faltante' }}: 
                                            ${{ number_format(abs($difference), 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-3 p-4 rounded-lg bg-success-50 dark:bg-success-900/20">
                                <div class="flex items-center">
                                    <x-filament::icon
                                        icon="heroicon-o-check-circle"
                                        class="h-5 w-5 text-success-600 mr-2"
                                    />
                                    <p class="text-sm font-semibold text-success-800 dark:text-success-200">
                                        ✅ Sin diferencias
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Botones de acción --}}
                    <div class="flex gap-3 justify-end">
                        <x-filament::button
                            wire:click="cancelarCierre"
                            color="gray"
                            :disabled="$this->isProcessing"
                        >
                            Cancelar
                        </x-filament::button>

                        <x-filament::button
                            wire:click="confirmarCierre"
                            color="primary"
                            icon="heroicon-o-lock-closed"
                            :disabled="$this->isProcessing"
                        >
                            @if ($this->isProcessing)
                                Procesando...
                            @else
                                Confirmar Cierre
                            @endif
                        </x-filament::button>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
@script
<script>
    $wire.on('abrir-pdf', (url) => {
        // Opción 1: Abrir en nueva pestaña
        window.open(url[0], '_blank');
        
        // Opción 2: Descargar automáticamente (descomenta si prefieres esta opción)
        // const link = document.createElement('a');
        // link.href = url[0];
        // link.download = '';
        // document.body.appendChild(link);
        // link.click();
        // document.body.removeChild(link);
    });
</script>
@endscript