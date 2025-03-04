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
        Schema::create('liquidacion_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('liquidaciones')->onDelete('cascade');
            $table->foreignId('entrega_id')->constrained('entregas')->onDelete('cascade');
            $table->decimal('monto_entrega', 10, 2);
            $table->decimal('qq_liquidado', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_detalle');
    }
};
