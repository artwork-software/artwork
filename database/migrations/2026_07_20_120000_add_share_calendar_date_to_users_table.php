<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Opt-in per user: all calendar-like views (calendar, planning calendar,
     * shift plan, their daily views and the shift list view) share one common
     * date range instead of remembering their own.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('share_calendar_date')->default(false)->after('shift_plan_daily_view');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('share_calendar_date');
        });
    }
};
