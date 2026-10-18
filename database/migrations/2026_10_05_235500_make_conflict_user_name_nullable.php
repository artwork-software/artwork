<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konflikte (Abwesenheit/Verfügbarkeit ↔ Schicht) speichern, wer eingeplant hat. Ist das unbekannt
 * (Altbestand ohne erfasste Zuweisung, ShiftSchedulerResolver liefert name = null), scheiterte das
 * Anlegen an NOT NULL – das Eintragen der Abwesenheit endete mit 500.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['vacation_conflicts', 'availabilities_conflicts'] as $table) {
            if (Schema::hasColumn($table, 'user_name')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('user_name')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['vacation_conflicts', 'availabilities_conflicts'] as $table) {
            if (Schema::hasColumn($table, 'user_name')) {
                // Seit up() angelegte Konflikte ohne Namen – sonst scheitert NOT NULL („Data truncated“)
                DB::table($table)->whereNull('user_name')->update(['user_name' => '']);
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('user_name')->nullable(false)->change();
                });
            }
        }
    }
};
