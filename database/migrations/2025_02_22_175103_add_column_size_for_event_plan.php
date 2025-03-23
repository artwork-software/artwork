<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('bulk_column_size')->default(json_encode([
                1 => 146,
                2 => 146,
                3 => 146,
                4 => 146,
                5 => 146,
                6 => 308,
            ], JSON_THROW_ON_ERROR))->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bulk_column_size');
        });
    }
};
