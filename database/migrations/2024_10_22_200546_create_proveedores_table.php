<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->string('nombrecompleto'); // Nombre del proveedor
            $table->string('cedula'); // Información de contacto
            $table->string('direccion')->nullable(); // Dirección
            $table->string('ciudad'); // Ciuidad del proveedor
            $table->string('municipio'); // Municipio
            $table->string('celular'); // Celular
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
