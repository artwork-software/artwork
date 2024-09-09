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
            $table->boolean('description')
                ->default(false)
                ->after('work_shifts');
            $table->boolean('use_project_time_period')
                ->default(false)
                ->after('description');
            $table->integer('time_period_project_id')
                ->default(0)
                ->after('use_project_time_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('use_project_time_period');
            $table->dropColumn('time_period_project_id');
        });
    }
};
