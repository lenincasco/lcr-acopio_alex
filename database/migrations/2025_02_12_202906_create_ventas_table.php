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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id(); // Clave primaria autoincremental

            // Claves foráneas con restricciones ON DELETE CASCADE
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('editado_por')->nullable()->constrained('users')->onDelete('cascade');

            $table->date('fecha_venta');
            $table->enum('tipo_cafe', ['UVA', 'PERGAMINO', 'MARA']);
            $table->integer('cantidad_sacos');
            $table->integer('tara_saco');
            $table->decimal('peso_bruto', 8, 2);
            $table->decimal('peso_neto', 8, 2);
            $table->decimal('humedad', 5, 2);
            $table->decimal('imperfeccion', 5, 2)->nullable();

            $table->decimal('tipo_cambio', 8, 3)->default(0);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('monto_bruto', 12, 2)->default(0);
            $table->decimal('iva', 8, 2)->default(0);
            $table->decimal('monto_neto', 12, 2)->default(0);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};