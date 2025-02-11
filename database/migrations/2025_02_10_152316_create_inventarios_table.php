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

            // Relación con la entrega que se registra en el inventario
            $table->unsignedBigInteger('entrega_id');
            $table->foreign('entrega_id')->references('id')->on('entregas')->onDelete('cascade');

            $table->date('fecha');
            $table->string('tipo')->default('entrada');
            $table->integer('cantidad')->default(0);
            $table->decimal('peso_neto', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventarios');
    }
}
