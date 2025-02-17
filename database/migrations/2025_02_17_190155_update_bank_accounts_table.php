<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function ($table) {
            $table->string('identity_prefix')->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function ($table) {
            $table->dropColumn('identity_prefix');
        });
    }
};
