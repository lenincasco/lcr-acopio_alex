<?php

namespace App\Filament\Resources\AbonoResource\Pages;

use App\Filament\Resources\AbonoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAbonos extends ListRecords
{
    protected static string $resource = AbonoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
