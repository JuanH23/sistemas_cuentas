<?php

namespace App\Filament\App\Resources\ProviderResource\Pages;

use App\Filament\App\Resources\ProviderResource;

use App\Filament\App\Resources;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
