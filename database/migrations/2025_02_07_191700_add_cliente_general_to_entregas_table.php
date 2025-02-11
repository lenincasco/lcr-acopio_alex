<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->string('cliente_general')->nullable()->comment('Nombre para proveedores no registrados');
        });
    }

    public function down()
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropColumn('cliente_general');
        });
    }
};
