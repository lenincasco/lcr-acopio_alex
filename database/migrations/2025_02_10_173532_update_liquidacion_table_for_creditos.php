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
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->renameColumn('monto_total', 'monto_bruto'); // Renombrar monto_total a monto_bruto
            $table->decimal('monto_credito_aplicado', 10, 2)->default(0.00)->after('estado'); // Añadir campo monto_credito_aplicado
            $table->decimal('monto_neto', 10, 2)->default(0.00)->after('monto_credito_aplicado'); // Añadir campo monto_neto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->renameColumn('monto_bruto', 'monto_total'); // Revertir el rename (opcional, si quieres volver al nombre original)
            $table->dropColumn('monto_credito_aplicado');
            $table->dropColumn('monto_neto');
        });
    }
};