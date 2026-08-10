<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_plan_daily_settings', function (Blueprint $table): void {
            // Projektfremde Termine/Schichten im Projekt-Schichten-Tab anzeigen
            // (andere Projekte oder ohne Projektzuordnung; default AUS)
            $table->boolean('show_unrelated_events')->default(false);
            $table->boolean('show_unrelated_shifts')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_plan_daily_settings', function (Blueprint $table): void {
            $table->dropColumn(['show_unrelated_events', 'show_unrelated_shifts']);
        });
    }
};
