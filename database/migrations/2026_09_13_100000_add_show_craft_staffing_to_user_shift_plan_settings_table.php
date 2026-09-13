<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_shift_plan_settings', 'show_craft_staffing')) {
                // Anzeigeeinstellung „Besetzung je Gewerk anzeigen": Besetzungs-Pille (besetzt/Bedarf)
                // in der Gewerkszeile des Personenbereichs (Tag + KW-Summe). Default an = bisheriges Verhalten.
                $table->boolean('show_craft_staffing')->default(true)->after('user_overview_light_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_shift_plan_settings', 'show_craft_staffing')) {
                $table->dropColumn('show_craft_staffing');
            }
        });
    }
};
