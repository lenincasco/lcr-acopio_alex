<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProveedorIdToLiquidacionesTable extends Migration
{
    public function up()
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            // Agrega el campo 'proveedor_id'
            $table->unsignedBigInteger('proveedor_id')->after('id');

            // Agrega la clave foránea (asegúrate de que la tabla 'proveedores' y la columna 'id' existan)
            $table->foreign('proveedor_id')
                ->references('id')
                ->on('proveedores')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            // Primero elimina la restricción de clave foránea y luego la columna
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
}
