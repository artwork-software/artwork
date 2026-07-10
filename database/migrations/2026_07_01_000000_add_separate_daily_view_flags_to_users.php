<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Split the shared "daily_view" toggle into two independent flags so the
     * calendar and the shift plan remember their view mode separately.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('calendar_daily_view')->default(false)->after('daily_view');
            $table->boolean('shift_plan_daily_view')->default(false)->after('calendar_daily_view');
        });

        // Backfill both new flags from the previously shared column.
        DB::table('users')->update([
            'calendar_daily_view' => DB::raw('daily_view'),
            'shift_plan_daily_view' => DB::raw('daily_view'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['calendar_daily_view', 'shift_plan_daily_view']);
        });
    }
};
