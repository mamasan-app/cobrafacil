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
        Schema::table('payments', function (Blueprint $table) {
            // Eliminar la columna antigua 'charged'
            $table->dropColumn('charged');

            // Agregar la nueva columna 'paid' con valores booleanos y por defecto 'false'
            $table->boolean('paid')->default(false)->after('is_bs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Eliminar la columna nueva 'paid'
            $table->dropColumn('paid');

            // Restaurar la columna 'charged' (en caso de rollback)
            $table->boolean('charged')->default(false)->after('is_bs');
        });
    }
};
