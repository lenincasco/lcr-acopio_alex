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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->date('fecha_desembolso');
            $table->decimal('interes', 5, 2);
            $table->decimal('plazo_meses', 4, 2);
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_interes', 10, 2)->nullable(); // Monto de interés
            $table->decimal('monto_total', 10, 2)->nullable(); // Monto total
            $table->decimal('volumen_estimado', 10, 2);
            $table->decimal('precio_referencia', 10, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_ultimo_pago')->nullable();
            $table->decimal('tipo_cambio',6,4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
