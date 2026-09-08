<?php

namespace Tests\Feature\Settings;

use Artwork\Modules\Holidays\Models\Holiday;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Backfill der neuen Spalte holidays.type: importierte Ferien (Name endet auf "ferien") -> school,
 * übrige importierte -> public, manuell angelegte -> custom. Die Migration ist idempotent
 * (hasColumn-Guard), up() lässt sich daher auf der bereits migrierten Test-DB erneut ausführen.
 */
final class HolidayTypeBackfillMigrationTest extends FeatureTestCase
{
    private function insertHoliday(string $name, bool $fromApi, string $type = 'custom'): int
    {
        return (int) DB::table('holidays')->insertGetId([
            'name' => $name,
            'date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'from_api' => $fromApi,
            'type' => $type,
            'yearly' => false,
            'treatAsSpecialDay' => false,
            'color' => '#333',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function backfill_derives_the_type_from_origin_and_name(): void
    {
        // Ausgangslage wie vor der Migration: alle Zeilen tragen den Spalten-Default "custom"
        $schoolId = $this->insertHoliday('Sommerferien', true);
        $publicId = $this->insertHoliday('Tag der Deutschen Einheit', true);
        $customId = $this->insertHoliday('Hausfeiertag', false);
        // Manuelle Einträge mit "ferien" im Namen bleiben custom (nur importierte werden umgeschlüsselt)
        $customFerienId = $this->insertHoliday('Betriebsferien', false);

        $migration = require database_path('migrations/2026_09_04_120000_add_type_to_holidays_table.php');
        $migration->up();

        $this->assertSame(Holiday::TYPE_SCHOOL, DB::table('holidays')->where('id', $schoolId)->value('type'));
        $this->assertSame(Holiday::TYPE_PUBLIC, DB::table('holidays')->where('id', $publicId)->value('type'));
        $this->assertSame(Holiday::TYPE_CUSTOM, DB::table('holidays')->where('id', $customId)->value('type'));
        $this->assertSame(Holiday::TYPE_CUSTOM, DB::table('holidays')->where('id', $customFerienId)->value('type'));

        // Zweiter Lauf ändert nichts
        $migration->up();
        $this->assertSame(Holiday::TYPE_SCHOOL, DB::table('holidays')->where('id', $schoolId)->value('type'));
    }

    #[Test]
    public function model_helpers_normalize_type_and_special_day_default(): void
    {
        $this->assertSame(Holiday::TYPE_PUBLIC, Holiday::normalizeType('Public'));
        $this->assertSame(Holiday::TYPE_SCHOOL, Holiday::normalizeType('school'));
        $this->assertSame(Holiday::TYPE_CUSTOM, Holiday::normalizeType('irgendwas'));
        $this->assertSame(Holiday::TYPE_CUSTOM, Holiday::normalizeType(null));

        $this->assertTrue(Holiday::defaultTreatAsSpecialDayFor(Holiday::TYPE_PUBLIC));
        $this->assertFalse(Holiday::defaultTreatAsSpecialDayFor(Holiday::TYPE_SCHOOL));
        $this->assertFalse(Holiday::defaultTreatAsSpecialDayFor(Holiday::TYPE_CUSTOM));
    }
}
