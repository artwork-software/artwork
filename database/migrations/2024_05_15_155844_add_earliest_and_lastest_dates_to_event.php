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
        Schema::table('events', function (Blueprint $table) {
            $table->datetime('earliest_start_datetime')->after('end_time')->nullable();
            $table->datetime('latest_end_datetime')->after('earliest_start_datetime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('earliest_start_datetime');
            $table->dropColumn('latest_end_datetime');
        });
    }
};
