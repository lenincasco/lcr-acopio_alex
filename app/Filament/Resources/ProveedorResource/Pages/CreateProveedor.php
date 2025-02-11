<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;

    //NEW function to redirect after new proveedor is created
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
