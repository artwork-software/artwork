<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indizes für Spalten, die im Hot Path nach Datum gefiltert werden und dafür
 * keinen Index hatten (EXPLAIN zeigte jeweils type=ALL):
 *
 * - shifts.event_start_day / event_end_day: die Schichtplan-Listenansicht filtert
 *   `event_start_day BETWEEN a AND b OR event_end_day BETWEEN a AND b`. Zwei
 *   Einzelindizes, damit MySQL sie per index_merge über das OR verknüpfen kann.
 * - holidays: hatte ausser PRIMARY gar keinen Index.
 * - vacations: hatte nur (vacationer_type, vacationer_id); die Schichtplan-
 *   Eager-Loads filtern zusätzlich über vacations.date.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{0: string, 1: string, 2: array<int, string>}>
     */
    private array $indexes = [
        ['shifts', 'shifts_event_start_day_index', ['event_start_day']],
        ['shifts', 'shifts_event_end_day_index', ['event_end_day']],
        ['holidays', 'holidays_date_end_date_index', ['date', 'end_date']],
        ['holidays', 'holidays_yearly_index', ['yearly']],
        [
            'vacations',
            'vacations_vacationer_date_index',
            ['vacationer_type', 'vacationer_id', 'date'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            if (!Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            if (!Schema::hasTable($table) || !Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        }
    }
};
