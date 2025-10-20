<?php

namespace App\Filament\Central\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/';
    
    protected static ?string $title = 'Panel Central';

    public function getWidgets(): array
    {
        return [
            // \App\Filament\App\Widgets\PlatformMovementsStatsWidget::class,
            // \App\Filament\App\Widgets\CerrarCaja::class,
            // \App\Filament\App\Widgets\FlujoInicialWidget::class,
        ];
    }
}