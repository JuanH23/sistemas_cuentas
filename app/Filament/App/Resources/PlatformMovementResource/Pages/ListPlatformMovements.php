<?php

namespace App\Filament\App\Resources\PlatformMovementResource\Pages;

use App\Filament\App\Resources\PlatformMovementResource;

use App\Filament\App\Resources;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformMovements extends ListRecords
{
    protected static string $resource = PlatformMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
