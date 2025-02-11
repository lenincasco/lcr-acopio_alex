<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [];// Retorna un array vacío para no mostrar create new
    }

    protected function getActions(): array
    {
        return []; // Retorna un array vacío para no mostrar acciones de los items de página
    }
}
