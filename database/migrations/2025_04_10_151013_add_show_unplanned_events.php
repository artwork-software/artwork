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
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->boolean('show_unplanned_events')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('show_unplanned_events');
        });
    }
};
