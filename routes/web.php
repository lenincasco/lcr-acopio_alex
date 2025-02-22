<?php

use App\Filament\Resources\PrestamoResource;
use Illuminate\Support\Facades\Route;
use App\Filament\Resources\EntregaResource;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/imprimir-recibo/{id}', [EntregaResource::class, 'imprimirRecibo'])->name('imprimir.recibo');
Route::get('/ver-pagare/{id}', [PrestamoResource::class, 'verPagare'])->name('single.pagare');

