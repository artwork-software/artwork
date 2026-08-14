<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'user_shift_plan_settings',
        'user_shift_plan_daily_settings',
    ];

    /**
     * Die parallelen Schichtplan-Requests (meta + rooms.batch) konnten beim
     * Erstaufruf beide eine Settings-Zeile anlegen; die hasOne-Relation löst
     * dann auf eine beliebige der Dubletten auf und Einstellungsänderungen
     * scheinen nicht zu greifen. Bestehende Dubletten werden entfernt (die
     * älteste Zeile — die hasOne-Auflösung — bleibt), danach sichert
     * unique(user_id) die Invariante gegen künftige Races ab (firstOrCreate/
     * updateOrCreate in den Schreibpfaden fangen den Verlierer ab).
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $keepIds = DB::table($table)
                ->selectRaw('MIN(id) as id')
                ->groupBy('user_id')
                ->pluck('id');

            DB::table($table)->whereNotIn('id', $keepIds)->delete();

            if (!Schema::hasIndex($table, ['user_id'], 'unique')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->unique('user_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, ['user_id'], 'unique')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropUnique(['user_id']);
                });
            }
        }
    }
};
