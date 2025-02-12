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
}
