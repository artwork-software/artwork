<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kalender und Planungskalender hatten dieselbe Einstellungszeile: „Geplante Termine anzeigen“ im
     * Kalender wirkte so auch im Planungskalender (und umgekehrt). Je User gibt es jetzt eine Zeile pro
     * Kalender (calendar_scope). Die Planungszeile startet als Kopie der bisherigen, damit niemand
     * seine Einstellungen verliert.
     */
    private const TABLES = [
        'user_calendar_settings',
        'user_daily_view_calendar_settings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!Schema::hasColumn($tableName, 'calendar_scope')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->string('calendar_scope', 16)->default('calendar')->after('user_id');
                    $table->index(['user_id', 'calendar_scope']);
                });
            }

            if (DB::table($tableName)->where('calendar_scope', 'planning')->exists()) {
                continue;
            }

            $columns = array_values(array_diff(Schema::getColumnListing($tableName), ['id', 'calendar_scope']));
            $columnList = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));

            DB::statement(
                "INSERT INTO `{$tableName}` ({$columnList}, `calendar_scope`) "
                . "SELECT {$columnList}, 'planning' FROM `{$tableName}` WHERE `calendar_scope` = 'calendar'"
            );
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!Schema::hasColumn($tableName, 'calendar_scope')) {
                continue;
            }
            DB::table($tableName)->where('calendar_scope', 'planning')->delete();
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['user_id', 'calendar_scope']);
                $table->dropColumn('calendar_scope');
            });
        }
    }
};
