<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konflikthinweise sollen sagen, was die genannte Person tatsächlich getan hat:
 * zugewiesen (shift_workers.assigned_by_user_id) oder nur festgeschrieben
 * (shifts.committing_user_id als Ersatzangabe für Altzuweisungen).
 *
 * Bestandszeilen werden über den Pivot nachgezogen; `scheduled_at` liefert den
 * Zeitpunkt der Zuweisung, der im Hinweis bisher komplett fehlte.
 */
return new class extends Migration
{
    private const TABLES = ['vacation_conflicts' => 'vacations', 'availabilities_conflicts' => 'availabilities'];

    public function up(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (!Schema::hasColumn($table, 'scheduler_source')) {
                    $blueprint->string('scheduler_source', 16)->nullable()->after('user_name');
                }

                if (!Schema::hasColumn($table, 'scheduled_at')) {
                    $blueprint->timestamp('scheduled_at')->nullable()->after('scheduler_source');
                }
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach (['scheduler_source', 'scheduled_at'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * Quelle und Zuweisungszeitpunkt aus dem Pivot nachtragen. Der Pivot trägt
     * den Basis-FQCN (User/Freelancer/ServiceProvider) — derselbe Wert steht im
     * morph-Typ der Verfügbarkeit/Abwesenheit, deshalb der direkte Vergleich.
     */
    private function backfill(): void
    {
        $ownerColumns = [
            'vacation_conflicts' => ['vacations', 'vacation_id', 'vacationer_type', 'vacationer_id'],
            'availabilities_conflicts' => ['availabilities', 'availability_id', 'available_type', 'available_id'],
        ];

        foreach ($ownerColumns as $table => [$ownerTable, $foreignKey, $typeColumn, $idColumn]) {
            if (!Schema::hasTable($table) || !Schema::hasTable($ownerTable) || !Schema::hasTable('shift_workers')) {
                continue;
            }

            DB::table($table)
                ->join($ownerTable, $ownerTable . '.id', '=', $table . '.' . $foreignKey)
                ->join('shift_workers', function ($join) use ($table, $ownerTable, $typeColumn, $idColumn): void {
                    $join->on('shift_workers.shift_id', '=', $table . '.shift_id')
                        ->on('shift_workers.employable_type', '=', $ownerTable . '.' . $typeColumn)
                        ->on('shift_workers.employable_id', '=', $ownerTable . '.' . $idColumn)
                        ->whereNull('shift_workers.deleted_at');
                })
                ->update([
                    $table . '.scheduler_source' => DB::raw(
                        "CASE WHEN shift_workers.assigned_by_user_id IS NULL THEN 'committed' ELSE 'assigned' END"
                    ),
                    $table . '.scheduled_at' => DB::raw('shift_workers.created_at'),
                ]);
        }
    }
};
