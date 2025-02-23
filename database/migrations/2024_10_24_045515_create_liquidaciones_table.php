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
            $table->decimal('tipo_cambio', 10, 4);
            $table->decimal('total_qq_liquidados', 10, 2);
            $table->decimal('precio_liquidacion', 10, 2);
            $table->enum('estado', ['activa', 'anulada'])->default('activa');
            $table->decimal('monto_neto', 10, 2);
            //prestamo data
            $table->decimal('intereses', 10, 2);
            $table->decimal('abono_capital', 10, 2);
            $table->unsignedBigInteger('prestamo_id')->after('id');
            $table->foreign('prestamo_id')
                ->references('id')
                ->on('prestamos')
                ->onDelete('cascade');
            $table->text('observaciones')->nullable();
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
