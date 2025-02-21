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
            $table->date('fecha_pago');
            $table->decimal('monto', 10, 2);
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
