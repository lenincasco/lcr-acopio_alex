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
            $table->integer('proveedor_id');// necesario para la intefaz de edit
            $table->decimal('tipo_cambio', 10, 4);
            $table->decimal('total_qq_liquidados', 10, 2);
            $table->decimal('total_qq_abonados', 10, 2);
            $table->decimal('precio_liquidacion', 10, 2);
            $table->boolean('activa')->default(true);
            $table->decimal('monto_neto', 10, 2);
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
