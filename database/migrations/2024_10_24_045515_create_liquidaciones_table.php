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
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_liquidacion');
            $table->foreignId('usuario_liquida')->constrained('users')->onDelete('cascade');
            $table->text('observacion')->nullable();
            $table->decimal('monto_total', 10, 2);
            $table->decimal('tipo_cambio', 10, 4);
            $table->decimal('total_qq_liquidados', 10, 2);
            $table->decimal('precio_liquidacion', 10, 2);
            $table->enum('estado', ['activa', 'anulada'])->default('activa');
            $table->decimal('monto_credito_aplicado', 10, 2)->default(0.00);
            $table->decimal('monto_neto', 10, 2)->default(0.00);


            $table->unsignedBigInteger('proveedor_id')->after('id');
            $table->foreign('proveedor_id')
                ->references('id')
                ->on('proveedores')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};
