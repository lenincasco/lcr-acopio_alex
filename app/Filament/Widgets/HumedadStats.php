<?php

namespace App\Filament\Resources\InventarioResource\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HumedadStats extends BaseWidget
{
  protected function getHeading(): ?string
  {
    return 'Cantidad de sacos por porcentaje de humedad';
  }

  protected function getStats(): array
  {
    $stats = [];
    //listar por humedad
    $data = Inventario::selectRaw('humedad, SUM(cantidad_sacos) as total_sacos')
      ->groupBy('humedad')
      ->orderBy('humedad', 'asc')
      ->get();

    foreach ($data as $item) {
      $stats[] = Stat::make("Humedad {$item->humedad}%", $item->total_sacos);
    }

    return $stats;
  }
}
