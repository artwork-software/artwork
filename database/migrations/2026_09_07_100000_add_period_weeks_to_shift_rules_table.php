<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regeltyp "Durchschnittliche Wochenstunden" (averageWeeklyHours): Ausgleichszeitraum in Wochen.
 * Nur für diesen Regeltyp gesetzt, sonst null.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shift_rules', 'period_weeks')) {
            Schema::table('shift_rules', function (Blueprint $table): void {
                $table->unsignedInteger('period_weeks')->nullable()->after('individual_number_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shift_rules', 'period_weeks')) {
            Schema::table('shift_rules', function (Blueprint $table): void {
                $table->dropColumn('period_weeks');
            });
        }
    }
};
