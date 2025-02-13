<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getTableHeaderContent(): ?string
    {
        // Consulta para calcular totales según el tipo de inventario
        $entradasCantidad = Inventario::where('tipo', 'entrada')->sum('cantidad');
        $salidasCantidad = Inventario::where('tipo', 'salida')->sum('cantidad');
        $entradasPeso = Inventario::where('tipo', 'entrada')->sum('peso_neto');
        $salidasPeso = Inventario::where('tipo', 'salida')->sum('peso_neto');

        // Puedes personalizar el HTML y la clase CSS a tu gusto.
        return '
            <div class="p-4 bg-white shadow rounded mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-bold">Entradas</h4>
                        <p>Cantidad: <span class="font-semibold">' . $entradasCantidad . '</span></p>
                        <p>Peso Neto: <span class="font-semibold">' . number_format($entradasPeso, 2) . '</span></p>
                    </div>
                    <div>
                        <h4 class="font-bold">Salidas</h4>
                        <p>Cantidad: <span class="font-semibold">' . $salidasCantidad . '</span></p>
                        <p>Peso Neto: <span class="font-semibold">' . number_format($salidasPeso, 2) . '</span></p>
                    </div>
                </div>
            </div>
        ';
    }

    protected function getActions(): array
    {
        return []; // Retorna un array vacío para no mostrar acciones de los items de página
    }
}
