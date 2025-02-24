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
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');  // Mantiene la liquidación si se elimina el usuario
            $table->foreignId('prestamo_id')
                ->constrained('prestamos')
                ->onDelete('restrict');  // Evita eliminar préstamos con liquidaciones activas

            $table->decimal('tipo_cambio', 10, 4);
            $table->decimal('total_qq_liquidados', 10, 2);
            $table->decimal('precio_liquidacion', 10, 2);
            $table->enum('estado', ['activa', 'anulada'])->default('activa');
            $table->decimal('monto_neto', 10, 2);
            //prestamo data
            $table->decimal('intereses', 10, 2);
            $table->decimal('abono_capital', 10, 2);
            $table->text('observaciones')->nullable();
            $table->date('fecha_liquidacion');
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
