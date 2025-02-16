<?php

namespace App\Filament\Resources\InventarioResource\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventarioStats extends BaseWidget
{
  protected function getHeading(): ?string
  {
    return 'Cantidad de sacos por tipo de café';
  }
  protected function getStats(): array
  {
    // Sumar la cantidad de café disponible por cada tipo
    $uva = Inventario::where('tipo_cafe', 'UVA')->sum('cantidad_sacos');
    $pergamino = Inventario::where('tipo_cafe', 'PERGAMINO')->sum('cantidad_sacos');
    $mara = Inventario::where('tipo_cafe', 'MARA')->sum('cantidad_sacos');

    return [
      Stat::make('Café UVA', $uva),
      Stat::make('Café PERGAMINO', $pergamino),
      Stat::make('Café MARA', $mara),
    ];
  }
}
