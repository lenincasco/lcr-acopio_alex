<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLiquidadaToEntregasTable extends Migration
{
    public function up()
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->boolean('liquidada')->default(false)->after('tipo_entrega');
        });
    }

    public function down()
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropColumn('liquidada');
        });
    }
}

