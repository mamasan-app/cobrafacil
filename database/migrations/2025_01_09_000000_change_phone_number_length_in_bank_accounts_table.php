<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('bank_code', 255)->change();
            $table->string('phone_number', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Reverting phone_number to length of 11
            $table->string('bank_code', 4)->change();
            $table->string('phone_number', 11)->change();
        });
    }
};
