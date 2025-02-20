<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Resources\EntregaResource;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/imprimir-recibo/{id}', [EntregaResource::class, 'imprimirRecibo'])->name('imprimir.recibo');

