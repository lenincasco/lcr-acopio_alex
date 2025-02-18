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
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_entrega', ['COMPRA', 'ENTREGA'])->default('ENTREGA');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->enum('tipo_cafe', ['UVA', 'PERGAMINO', 'MARA']);
            $table->enum('humedad', ['OREADO', 'HUMEDO', 'MOJADO']);
            $table->enum('calidad', ['PRIMERA', 'IMPERFECTO']);

            $table->integer('cantidad_sacos'); // Cantidad de sacos
            $table->decimal('peso_bruto', 8, 2); // Peso bruto en quintales
            $table->integer('tara_saco'); // Tara de sacos
            $table->decimal('peso_neto', 8, 2); // Peso neto (calculado automáticamente)
            $table->decimal('quintalaje_liquidable', 8, 2); // Quintalaje sobre el cual se liquida

            $table->date('fecha_entrega'); // Cantidad de sacos
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('editado_por')->nullable()->constrained('users')->onDelete('cascade');

            $table->boolean('liquidada')->default(false);
            $table->decimal('precio_compra', 5, 2); //NEW
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
