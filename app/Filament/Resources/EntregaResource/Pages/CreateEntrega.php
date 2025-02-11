<?php

namespace App\Filament\Resources\EntregaResource\Pages;

use App\Filament\Resources\EntregaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEntrega extends CreateRecord
{
    protected static string $resource = EntregaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creado_por'] = auth()->id();

        return $data;
    }

    //NEW
    protected function getFormActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Liquidación') // Opcional: Puedes personalizar la etiqueta si lo deseas
                ->key('create') // Opcional: Clave única para la acción
                ->disable(function (callable $get) {
                    // Deshabilitar el botón "Crear" si NO hay detalles de liquidación
                    $detalleLiquidacion = $get('detalle_liquidacion') ?? [];
                    return empty($detalleLiquidacion); // Deshabilitar si el array de detalles está vacío
                }),
        ];
    }

}
