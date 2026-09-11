<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_shift_plan_settings', 'user_overview_light_mode')) {
                // Personenbereich (unteres Panel) der Schichtplan-Wochenansicht hell statt dunkel
                // darstellen — pro Person, Umschalter (Sonne/Mond) direkt im Panel
                $table->boolean('user_overview_light_mode')->default(false)->after('show_user_overview');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_shift_plan_settings', 'user_overview_light_mode')) {
                $table->dropColumn('user_overview_light_mode');
            }
        });
    }
};
