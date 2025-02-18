<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('editado_por')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('tipo_salida')->default('REMISION');
            $table->date('fecha_salida');
            $table->integer('cantidad_sacos');
            $table->integer('tara_saco');
            $table->decimal('peso_bruto', 8, 2);
            $table->decimal('peso_neto', 8, 2);
            $table->decimal('humedad', 5, 2);
            $table->decimal('calidad', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salidas');
    }
};
