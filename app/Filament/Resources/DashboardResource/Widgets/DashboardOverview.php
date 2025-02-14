<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Models\Entrega;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make("Entregas", Entrega::count())
        ];
    }
}
