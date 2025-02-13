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
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('editado_por')->nullable()->constrained('users')->onDelete('cascade');    
            $table->enum('tipo_entrega', ['COMPRA', 'ENTREGA'])->default('ENTREGA');
            $table->enum('estado', ['LIQUIDADO', 'PENDIENTE'])->default('PENDIENTE');
            $table->date('fecha_entrega'); // Cantidad de sacos
            $table->integer('cantidad_sacos'); // Cantidad de sacos
            $table->integer('tara_saco'); // Tara de sacos
            $table->decimal('peso_bruto', 8, 2); // Peso bruto en quintales
            $table->decimal('peso_neto', 8, 2); // Peso neto (calculado automáticamente)
            $table->decimal('quintalaje_liquidable', 8, 2); // Quintalaje sobre el cual se liquida
            $table->decimal('humedad', 5, 2); // Porcentaje de humedad
            $table->decimal('imperfeccion', 5, 2); // Porcentaje de imperfección
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
