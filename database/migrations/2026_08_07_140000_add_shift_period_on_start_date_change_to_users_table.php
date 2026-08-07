<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-user editing behavior: when the start date of an event is changed,
     * either keep the end date fixed (default) or shift the whole period so the
     * duration is preserved. Applies to the bulk list and the event modal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('shift_period_on_start_date_change')->default(false)->after('share_calendar_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('shift_period_on_start_date_change');
        });
    }
};
