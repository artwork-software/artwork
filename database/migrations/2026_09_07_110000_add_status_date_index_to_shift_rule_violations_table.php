<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die paginierte Verstoßliste filtert nach status und sortiert nach violation_date/id.
 * Ohne diesen Index läuft die Abfrage als Full-Scan mit Filesort (Tabelle wächst je Neuprüfung).
 */
return new class extends Migration
{
    private const INDEX = 'shift_rule_violations_status_date_id_index';

    public function up(): void
    {
        if (!Schema::hasTable('shift_rule_violations') || Schema::hasIndex('shift_rule_violations', self::INDEX)) {
            return;
        }

        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->index(['status', 'violation_date', 'id'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('shift_rule_violations') && Schema::hasIndex('shift_rule_violations', self::INDEX)) {
            Schema::table('shift_rule_violations', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX);
            });
        }
    }
};
