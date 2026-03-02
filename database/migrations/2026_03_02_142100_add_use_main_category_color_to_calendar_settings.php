<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->boolean('use_main_category_color')->default(false)->after('use_event_status_color');
        });

        Schema::table('user_daily_view_calendar_settings', function (Blueprint $table) {
            $table->boolean('use_main_category_color')->default(false)->after('use_event_status_color');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('use_main_category_color');
        });

        Schema::table('user_daily_view_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('use_main_category_color');
        });
    }
};
