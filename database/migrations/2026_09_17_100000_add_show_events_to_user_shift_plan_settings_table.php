<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_shift_plan_settings', 'show_events')) {
                // Anzeigeeinstellung „Termine anzeigen" (Dienstplan-Wochenansicht): blendet die
                // Termin-Kacheln aus, damit nur Schichten sichtbar sind. Default an = bisheriges
                // Verhalten für alle bestehenden Nutzer:innen.
                $table->boolean('show_events')->default(true)->after('show_craft_staffing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_shift_plan_settings', 'show_events')) {
                $table->dropColumn('show_events');
            }
        });
    }
};
