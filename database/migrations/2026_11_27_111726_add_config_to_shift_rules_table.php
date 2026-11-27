<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zusätzliche Parameter je Regeltyp (z. B. Wochentag und Uhrzeit der Wochenplan-Frist). Regeln
     * ohne config verhalten sich wie bisher.
     */
    public function up(): void
    {
        Schema::table('shift_rules', function (Blueprint $table): void {
            $table->json('config')->nullable()->after('period_weeks');
        });
    }

    public function down(): void
    {
        Schema::table('shift_rules', function (Blueprint $table): void {
            $table->dropColumn('config');
        });
    }
};
