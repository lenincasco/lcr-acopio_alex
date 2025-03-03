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
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
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
