<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Der Vertragsschalter "Sondertag-Regel aktiv" hatte bisher keinen Konsumenten; Sondertage senkten
 * das Tagessoll für alle. Ab jetzt wirkt der Schalter (aus = Sondertage zählen für die Person nicht).
 * Damit sich das Verhalten für Bestandsdaten nicht ändert, werden vorhandene Verträge und
 * Zuweisungen auf "aktiv" gesetzt und der Default auf 1 gehoben.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['user_contracts', 'user_contract_assigns'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'special_day_rule_active')) {
                continue;
            }

            DB::table($table)->update(['special_day_rule_active' => true]);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->boolean('special_day_rule_active')->default(true)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['user_contracts', 'user_contract_assigns'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'special_day_rule_active')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->boolean('special_day_rule_active')->default(false)->change();
            });
        }
    }
};
