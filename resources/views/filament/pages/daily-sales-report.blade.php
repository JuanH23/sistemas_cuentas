<x-filament::page>
    <div class="space-y-6">
        {{-- Header con controles --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard de Ventas</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitorea el rendimiento de tu negocio en tiempo real
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                    <select wire:model.live="filtroPeriodo" class="text-sm border-0 bg-transparent focus:ring-0 text-gray-700 dark:text-gray-200">
                        <option value="hoy">Hoy</option>
                        <option value="7dias">Últimos 7 días</option>
                        <option value="mes">Este mes</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- KPIs Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Total Ventas --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium opacity-90">Total Ventas</p>
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                            <x-heroicon-o-currency-dollar class="w-5 h-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold mb-1">
                        ${{ number_format($periodSalesTotal, 0, ',', '.') }}
                    </p>
                    @if($cambioPorcentaje !== null)
                        <p class="text-sm opacity-75">
                            @if($cambioPorcentaje >= 0)
                                <span class="inline-flex items-center">
                                    <x-heroicon-s-arrow-trending-up class="w-4 h-4 mr-1" />
                                    +{{ abs($cambioPorcentaje) }}% vs ayer
                                </span>
                            @else
                                <span class="inline-flex items-center">
                                    <x-heroicon-s-arrow-trending-down class="w-4 h-4 mr-1" />
                                    {{ $cambioPorcentaje }}% vs ayer
                                </span>
                            @endif
                        </p>
                    @endif
                </div>
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            </div>

            {{-- Flujo Neto --}}
            <div class="relative overflow-hidden bg-gradient-to-br {{ $netFlow >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600' }} rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium opacity-90">Flujo Neto</p>
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                            <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                        </div>
                    </div>
                    <p class="text-3xl font-bold mb-1">
                        ${{ number_format(abs($netFlow), 0, ',', '.') }}
                    </p>
                    <p class="text-sm opacity-75">
                        {{ $netFlow >= 0 ? 'Superávit' : 'Déficit' }} del período
                    </p>
                </div>
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            </div>

            {{-- Acciones Rápidas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Exportar Reportes</p>
                <div class="space-y-2">
                    <button wire:click="exportSalesPdf" class="w-full flex items-center justify-between px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        <span class="text-sm font-medium">Ventas</span>
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </button>
                    <button wire:click="exportIncomeExpensePdf" class="w-full flex items-center justify-between px-4 py-2.5 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
                        <span class="text-sm font-medium">Ingresos/Egresos</span>
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </button>
                    <button wire:click="exportInventoryPdf" class="w-full flex items-center justify-between px-4 py-2.5 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        <span class="text-sm font-medium">Inventario</span>
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Productos más vendidos --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Productos más vendidos</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Top 5</span>
                </div>
                
                @if(count($topProducts) > 0)
                    <div 
                        wire:ignore
                        x-data="{ loaded: false }"
                        x-init="
                            setTimeout(() => {
                                const ctx = $refs.canvas.getContext('2d');
                                new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: {{ Js::from(collect($topProducts)->pluck('name')) }},
                                        datasets: [{
                                            label: 'Unidades vendidas',
                                            data: {{ Js::from(collect($topProducts)->pluck('total_vendidos')) }},
                                            backgroundColor: '#3b82f6',
                                            borderRadius: 6,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: { 
                                                    font: { size: 10 },
                                                    stepSize: 1
                                                },
                                                grid: {
                                                    color: 'rgba(0,0,0,0.05)'
                                                }
                                            },
                                            x: {
                                                ticks: { font: { size: 10 } },
                                                grid: { display: false }
                                            }
                                        }
                                    }
                                });
                                loaded = true;
                            }, 300);
                        "
                        class="h-48 relative"
                    >
                        <canvas x-ref="canvas" class="w-full h-full"></canvas>
                    </div>
                @else
                    <div class="h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-chart-bar class="w-10 h-10 mb-2 opacity-50" />
                        <p class="text-xs">No hay datos</p>
                    </div>
                @endif
            </div>

            {{-- Plataformas más usadas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Plataformas de pago</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Distribución</span>
                </div>
                
                @if(count($topPlatforms) > 0)
                    <div 
                        wire:ignore
                        x-data="{ loaded: false }"
                        x-init="
                            setTimeout(() => {
                                const ctx = $refs.canvas.getContext('2d');
                                new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: {{ Js::from(collect($topPlatforms)->pluck('platform')) }},
                                        datasets: [{
                                            data: {{ Js::from(collect($topPlatforms)->pluck('total_usos')) }},
                                            backgroundColor: ['#10b981','#3b82f6','#f59e0b','#8b5cf6','#ef4444'],
                                            borderWidth: 0,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                                labels: { 
                                                    font: { size: 9 },
                                                    padding: 8,
                                                    usePointStyle: true
                                                }
                                            }
                                        }
                                    }
                                });
                                loaded = true;
                            }, 300);
                        "
                        class="h-48 relative"
                    >
                        <canvas x-ref="canvas" class="w-full h-full"></canvas>
                    </div>
                @else
                    <div class="h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-credit-card class="w-10 h-10 mb-2 opacity-50" />
                        <p class="text-xs">No hay datos</p>
                    </div>
                @endif
            </div>

            {{-- Ventas últimos 7 días --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tendencia de ventas</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">7 días</span>
                </div>
                
                @if(count($weeklySales) > 0)
                    <div 
                        wire:ignore
                        x-data="{ loaded: false }"
                        x-init="
                            setTimeout(() => {
                                const ctx = $refs.canvas.getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: {{ Js::from(collect($weeklySales)->pluck('fecha')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) }},
                                        datasets: [{
                                            label: 'Ventas',
                                            data: {{ Js::from(collect($weeklySales)->pluck('total')) }},
                                            borderColor: '#6366f1',
                                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                            fill: true,
                                            tension: 0.4,
                                            pointRadius: 3,
                                            pointHoverRadius: 5,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: { font: { size: 10 } },
                                                grid: { color: 'rgba(0,0,0,0.05)' }
                                            },
                                            x: {
                                                ticks: { font: { size: 10 } },
                                                grid: { display: false }
                                            }
                                        }
                                    }
                                });
                                loaded = true;
                            }, 300);
                        "
                        class="h-48 relative"
                    >
                        <canvas x-ref="canvas" class="w-full h-full"></canvas>
                    </div>
                @else
                    <div class="h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-chart-bar-square class="w-10 h-10 mb-2 opacity-50" />
                        <p class="text-xs">No hay datos</p>
                    </div>
                @endif
            </div>

            {{-- Ingresos vs Egresos --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Flujo de caja</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Comparativa</span>
                </div>
                
                @if(count($weeklyInOut) > 0)
                    <div 
                        wire:ignore
                        x-data="{ loaded: false }"
                        x-init="
                            setTimeout(() => {
                                const ctx = $refs.canvas.getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: {{ Js::from(collect($weeklyInOut)->pluck('fecha')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) }},
                                        datasets: [
                                            {
                                                label: 'Ingresos',
                                                data: {{ Js::from(collect($weeklyInOut)->pluck('ingresos')) }},
                                                borderColor: '#10b981',
                                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 2,
                                            },
                                            {
                                                label: 'Egresos',
                                                data: {{ Js::from(collect($weeklyInOut)->pluck('egresos')) }},
                                                borderColor: '#ef4444',
                                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 2,
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        interaction: {
                                            mode: 'index',
                                            intersect: false,
                                        },
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                                align: 'end',
                                                labels: { 
                                                    font: { size: 9 },
                                                    usePointStyle: true,
                                                    padding: 8
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: { font: { size: 10 } },
                                                grid: { color: 'rgba(0,0,0,0.05)' }
                                            },
                                            x: {
                                                ticks: { font: { size: 10 } },
                                                grid: { display: false }
                                            }
                                        }
                                    }
                                });
                                loaded = true;
                            }, 300);
                        "
                        class="h-48 relative"
                    >
                        <canvas x-ref="canvas" class="w-full h-full"></canvas>
                    </div>
                @else
                    <div class="h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-arrow-trending-up class="w-10 h-10 mb-2 opacity-50" />
                        <p class="text-xs">No hay datos</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament::page>