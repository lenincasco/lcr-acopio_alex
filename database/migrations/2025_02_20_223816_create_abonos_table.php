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
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id');
            $table->foreignId('liquidacion_id')
                ->nullable()
                ->constrained('liquidaciones')
                ->onDelete('cascade'); // Elimina abonos si se borra la liquidación
            $table->date('fecha_pago');
            $table->decimal('abono_capital', 10, 2);//monto - capital
            $table->decimal('intereses', 10, 2);//interes en moneda
            $table->integer('dias_diff');
            $table->integer('qq_abonados')->default(0);//campo para liquidaciones
            $table->text('observaciones')->nullable();
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
            $table->enum('estado', ['ACTIVO', 'ANULADO'])->default('ACTIVO');
            $table->date('fecha_anula')->nullable();
            $table->string('usuario_anula')->nullable();
            $table->string('razon_anula')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
