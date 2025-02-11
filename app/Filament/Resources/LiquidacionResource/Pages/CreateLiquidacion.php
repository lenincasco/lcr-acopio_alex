<?php

namespace App\Filament\Resources\LiquidacionResource\Pages;

use App\Filament\Resources\LiquidacionResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

class CreateLiquidacion extends CreateRecord
{
    protected static string $resource = LiquidacionResource::class;

    protected function getFormActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->disable(function (callable $get) {
                    // Disable the "Create" button if there are NO liquidation details
                    $detalleLiquidacion = $get('detalle_liquidacion') ?? [];
                    return empty($detalleLiquidacion); // Disable if the details array is empty
                }),
        ];
    }

}
