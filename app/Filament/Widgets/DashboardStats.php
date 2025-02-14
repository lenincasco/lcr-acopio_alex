<?php

namespace App\Filament\Widgets;

use App\Models\Entrega;
use App\Models\Liquidacion;
use App\Models\Prestamo;
use App\Models\Venta;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DashboardStats extends BaseWidget
{
  protected function getStats(): array
  {
    return [
      Stat::make("Entregas", Entrega::count()),
      Stat::make("Ventas", Venta::count()),
      Stat::make("Liquidaciones", Liquidacion::count()),
      Stat::make("Préstamos", Prestamo::count()),
    ];
  }
}
