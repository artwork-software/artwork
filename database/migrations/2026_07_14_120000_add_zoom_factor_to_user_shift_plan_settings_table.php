<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_shift_plan_settings', 'zoom_factor')) {
                // Tagesspaltenbreiten-Zoom der Schichtplan-Wochenansicht (0.55/0.75/1),
                // bewusst getrennt vom Kalender-Zoom (users.zoom_factor)
                $table->float('zoom_factor')->default(1)->after('expand_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_shift_plan_settings', 'zoom_factor')) {
                $table->dropColumn('zoom_factor');
            }
        });
    }
};
