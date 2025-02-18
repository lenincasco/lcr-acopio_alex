<?php

namespace App\Filament\Resources\InventarioResource\Widgets;

use App\Models\Inventario;
use Cache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventarioStats extends BaseWidget
{
    protected ?string $heading = 'Stock de Café';
    protected int|string|array $columnSpan = 'full'; // Ocupar ancho completo

    protected function getStats(): array
    {
        $stock = Cache::remember('coffee-stats', 3600, function () {
            return Inventario::selectRaw('tipo_cafe, SUM(cantidad_sacos) as total')
                ->groupBy('tipo_cafe')
                ->get()
                ->keyBy('tipo_cafe');
        });

        return [
            Stat::make('UVA', $stock->get('UVA')->total ?? 0)
                ->description('Sacos')
                ->color('amber'),

            Stat::make('PERGAMINO', $stock->get('PERGAMINO')->total ?? 0)
                ->description('Sacos')
                ->color('emerald'),

            Stat::make('MARA', $stock->get('MARA')->total ?? 0)
                ->description('Sacos')
                ->color('rose'),
        ];
    }
}