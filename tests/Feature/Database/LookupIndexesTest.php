<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Migration 2026_11_16_101457: Lese-Spalten ohne Index (Projektdateien, Serien, Raum-Pivots). */
class LookupIndexesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function expectedIndexes(): array
    {
        return [
            'Projektdateien je Projekt' => ['project_files', ['project_id']],
            'Abwesenheits-Serien' => ['vacations', ['series_id']],
            'Verfügbarkeits-Serien' => ['availabilities', ['series_id']],
            'Raum-Kategorien' => ['room_room_category', ['room_id']],
            'Raum-Eigenschaften' => ['room_room_attribute', ['room_id']],
            'Datei-Freigaben' => ['project_file_user', ['project_file_id']],
            // Migration 2026_11_23_152843: chronologische Sortierung der Projektübersicht
            'Erster Termin je Projekt' => ['events', ['project_id', 'deleted_at', 'start_time']],
            'Letzter Termin je Projekt' => ['events', ['project_id', 'deleted_at', 'end_time']],
            // Migration 2026_11_23_154938: Projektkopf, Schichtverlauf, Arbeitszeitbuchungen
            'Erste Schicht je Projekt' => ['shifts', ['project_id', 'deleted_at', 'start_date']],
            'Letzte Schicht je Projekt' => ['shifts', ['project_id', 'deleted_at', 'end_date']],
            'Gelöschte Schichten im Verlauf' => [
                'activity_log',
                ['log_name', 'subject_type', 'event', 'subject_id'],
            ],
            'Buchungen je Person und Tag' => ['work_time_bookings', ['user_id', 'booking_day']],
        ];
    }

    /**
     * @param list<string> $columns
     */
    #[Test]
    #[DataProvider('expectedIndexes')]
    public function lookup_column_is_indexed(string $tableName, array $columns): void
    {
        $indexedColumns = collect(Schema::getIndexes($tableName))->pluck('columns')->all();

        self::assertContains($columns, $indexedColumns);
    }
}
