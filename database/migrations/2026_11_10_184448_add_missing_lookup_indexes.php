<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabellen, die bisher nur den Primärschlüssel hatten, obwohl Dashboard, Aufgaben, Projektteams,
     * Kalender (Timelines, Unter-Termine) und der Anfragen-Eingang ständig über diese Spalten filtern.
     *
     * @var array<string, list<list<string>>>
     */
    private const INDEXES = [
        'project_user' => [['project_id', 'user_id'], ['user_id']],
        'task_user' => [['task_id', 'user_id'], ['user_id']],
        'tasks' => [['checklist_id'], ['contract_id']],
        'project_shift_relevant_event_types' => [['project_id']],
        'project_shift_contacts' => [['project_id', 'user_id']],
        'project_sector' => [['project_id']],
        'genre_project' => [['project_id']],
        'project_groups' => [['project_id'], ['group_id']],
        'timelines' => [['event_id']],
        'sub_events' => [['event_id']],
        'events' => [['occupancy_option']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
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
