<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abfragen, die bisher mehr Zeilen lasen als nötig:
     * - shifts: erste und letzte Schicht je Projekt (Projektkopf, bei jedem Öffnen eines Projekts)
     * - activity_log: gelöschte Schichten im Schichtverlauf; las alle Schicht-Protokollzeilen
     * - work_time_bookings: Buchungen einer Person im Zeitraum; las die ganze Historie der Person
     *
     * @var array<string, list<list<string>>>
     */
    private const INDEXES = [
        'shifts' => [
            ['project_id', 'deleted_at', 'start_date'],
            ['project_id', 'deleted_at', 'end_date'],
        ],
        'activity_log' => [['log_name', 'subject_type', 'event', 'subject_id']],
        'work_time_bookings' => [['user_id', 'booking_day']],
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
            foreach ($indexes as $columns) {
                $indexedColumns = collect(Schema::getIndexes($tableName))->pluck('columns');

                if (!$indexedColumns->contains($columns)) {
                    continue;
                }

                // MariaDB entfernt den automatisch angelegten Fremdschlüssel-Index, sobald ein neuer Index
                // mit derselben führenden Spalte existiert. Vor dem Entfernen muss deshalb wieder ein
                // anderer Index auf dieser Spalte stehen, sonst scheitert das Zurückrollen.
                $leadingColumn = $columns[0];
                $hasOtherIndexOnLeadingColumn = $indexedColumns
                    ->reject(fn (array $indexed): bool => $indexed === $columns)
                    ->contains(fn (array $indexed): bool => $indexed[0] === $leadingColumn);

                if (!$hasOtherIndexOnLeadingColumn) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->index([$leadingColumn]));
                }

                Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($columns));
            }
        }
    }
};
