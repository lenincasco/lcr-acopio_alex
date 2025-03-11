<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha')->default(now());
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('monto', 10, 2);
            $table->string('concepto'); // Puedes hacer otra tabla si prefieres
            $table->string('referencia')->nullable(); // Número de comprobante o factura (opcional)
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('detalle')->nullable();
            $table->enum('estado', ['ACTIVO', 'ANULADO'])->default('ACTIVO');
            $table->date('fecha_anula')->nullable();
            $table->string('usuario_anula')->nullable();
            $table->string('razon_anula')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
