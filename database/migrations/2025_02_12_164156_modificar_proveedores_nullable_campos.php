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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('cedula')->nullable()->change();
            $table->string('ciudad')->nullable()->change();
            $table->string('municipio')->nullable()->change();
            $table->string('celular')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('cedula')->nullable(false)->change();
            $table->string('ciudad')->nullable(false)->change();
            $table->string('municipio')->nullable(false)->change();
            $table->string('celular')->nullable(false)->change();
        });
    }
};