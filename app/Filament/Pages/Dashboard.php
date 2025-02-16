<?php
namespace App\Filament\Pages;

use App\Filament\Resources\InventarioResource\Widgets\InventarioStats;
use App\Filament\Widgets\DashboardStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
  protected static ?string $navigationLabel = 'Panel Principal';

  protected static string $view = 'filament.pages.dashboard';

  // Configurar widgets
  protected function getHeaderWidgets(): array
  {
    return [
      DashboardStats::class,
      InventarioStats::class,
    ];
  }

}