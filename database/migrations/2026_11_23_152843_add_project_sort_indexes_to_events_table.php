<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Die chronologische Sortierung der Projektübersicht sucht je Projekt den ersten und den letzten
     * nicht gelöschten Termin (Event::scopeGetOrderBySubQueryBuilder). Mit Einzelindizes hängt die
     * Laufzeit davon ab, welchen Index die Datenbank wählt; bei einigen tausend Projekten lief die
     * Abfrage in den Timeout. Diese Indizes liefern den gesuchten Termin je Projekt direkt.
     *
     * @var list<list<string>>
     */
    private const INDEXES = [
        ['project_id', 'deleted_at', 'start_time'],
        ['project_id', 'deleted_at', 'end_time'],
    ];

    public function up(): void
    {
        $indexedColumns = collect(Schema::getIndexes('events'))->pluck('columns')->all();

        Schema::table('events', function (Blueprint $table) use ($indexedColumns): void {
            foreach (self::INDEXES as $columns) {
                if (!in_array($columns, $indexedColumns, true)) {
                    $table->index($columns);
                }
            }
        });
    }

    public function down(): void
    {
        foreach (self::INDEXES as $columns) {
            $indexedColumns = collect(Schema::getIndexes('events'))->pluck('columns');

            if (!$indexedColumns->contains($columns)) {
                continue;
            }

            // MariaDB entfernt den automatisch angelegten Fremdschlüssel-Index auf project_id, sobald ein
            // neuer Index mit derselben führenden Spalte existiert. Vor dem Entfernen muss deshalb wieder
            // ein anderer Index auf project_id stehen, sonst scheitert das Zurückrollen.
            $hasOtherProjectIndex = $indexedColumns
                ->reject(fn (array $indexed): bool => $indexed === $columns)
                ->contains(fn (array $indexed): bool => $indexed[0] === 'project_id');

            if (!$hasOtherProjectIndex) {
                Schema::table('events', fn (Blueprint $table) => $table->index(['project_id']));
            }

            Schema::table('events', fn (Blueprint $table) => $table->dropIndex($columns));
        }
    }
};
