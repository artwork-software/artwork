<?php

namespace Tests\Feature;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Regressionstests für die Duplikat-Rows in der settings-Tabelle: Der
 * Migration-Squash hatte den Unique-Index auf (group, name) verloren, wodurch
 * Spaties upsert() bei jedem Speichern neue Rows anlegte statt zu aktualisieren.
 */
class SettingsTableIntegrityTest extends FeatureTestCase
{
    public function testSettingsTableHasUniqueIndexOnGroupAndName(): void
    {
        $this->assertTrue(
            Schema::hasIndex('settings', ['group', 'name'], 'unique'),
            'settings braucht einen Unique-Index auf (group, name), sonst legt Spaties upsert() Duplikate an.'
        );
    }

    public function testSavingSettingsRepeatedlyDoesNotCreateDuplicateRows(): void
    {
        $settings = app(GeneralSettings::class);

        $settings->allowed_project_file_size = 111;
        $settings->save();

        $settings->allowed_project_file_size = 222;
        $settings->save();

        $rows = DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'allowed_project_file_size')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(222, json_decode($rows->first()->payload));
    }
}
