<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Widgets\HumedadStats;
use App\Filament\Resources\InventarioResource\Widgets\InventarioStats;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    // Sobrescribe este método para agregar widgets en el header
    protected function getHeaderWidgets(): array
    {
        return [
            InventarioStats::class,
            HumedadStats::class,
        ];
    }


    protected function getActions(): array
    {
        return []; // Retorna un array vacío para no mostrar acciones de los items de página
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false);//Disabled pagination
    }
}
