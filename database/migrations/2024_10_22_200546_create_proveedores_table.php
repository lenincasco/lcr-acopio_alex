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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->string('nombrecompleto');
            $table->string('direccion')->nullable();
            //valores nullables en caso que el proveedor sea informal
            $table->string('cedula')->nullable();
            $table->string('ciudad');
            $table->string('municipio')->nullable();
            $table->string('celular')->nullable();
            $table->decimal('credito_disponible', 10, 2)->default(0.00);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
