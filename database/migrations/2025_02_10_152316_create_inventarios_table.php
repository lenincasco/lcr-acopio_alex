<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventariosTable extends Migration
{
    public function up()
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();


            $table->date('fecha')->nullable()->comment('Fecha de la última actualización');
            $table->enum('tipo', ['ENTREGA', 'COMPRA', 'SALIDA', 'VENTA']);
            $table->enum('tipo_cafe', ['UVA', 'PERGAMINO', 'MARA']);
            $table->decimal('humedad', 5, 2);

            $table->integer('cantidad_sacos')->default(0);
            $table->decimal('peso_neto', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventarios');
    }
}
