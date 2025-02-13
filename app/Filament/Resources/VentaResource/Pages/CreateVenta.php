<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creado_por'] = auth()->id();

        return $data;
    }
}
