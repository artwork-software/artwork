<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spalten ohne Index, über die regelmäßig gelesen wird: Projektdateien je Projekt (Projektseite,
     * Dokumente, BI), Serien von Abwesenheiten/Verfügbarkeiten (Serien-Grenzen im Dienstplan) und
     * die Pivot-Tabellen von Raum-Kategorien/-Eigenschaften und Datei-Freigaben.
     *
     * @var array<string, list<list<string>>>
     */
    private const INDEXES = [
        'project_files' => [['project_id']],
        'vacations' => [['series_id']],
        'availabilities' => [['series_id']],
        'room_room_category' => [['room_id'], ['room_category_id']],
        'room_room_attribute' => [['room_id'], ['room_attribute_id']],
        'project_file_user' => [['project_file_id'], ['user_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $indexedColumns = collect(Schema::getIndexes($tableName))->pluck('columns')->all();

            Schema::table($tableName, function (Blueprint $table) use ($indexes, $indexedColumns): void {
                foreach ($indexes as $columns) {
                    if (!in_array($columns, $indexedColumns, true)) {
                        $table->index($columns);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $indexedColumns = collect(Schema::getIndexes($tableName))->pluck('columns')->all();

            Schema::table($tableName, function (Blueprint $table) use ($indexes, $indexedColumns): void {
                foreach ($indexes as $columns) {
                    if (in_array($columns, $indexedColumns, true)) {
                        $table->dropIndex($columns);
                    }
                }
            });
        }
    }
};
