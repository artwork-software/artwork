<?php

namespace Tests\Feature\Settings;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;
use Tests\Feature\FeatureTestCase;

/**
 * Die Migration seedet das Einmal-Flag granular_defaults_granted abhängig davon,
 * ob granulare Rechte auf der Instanz bereits aktiv sind — sonst würde das nächste
 * Off→On auf einer Bestandsinstanz die Default-Rechte erneut verteilen.
 */
final class GranularDefaultsGrantedMigrationTest extends FeatureTestCase
{
    private function rerunMigration(): void
    {
        // Ausgangszustand herstellen: Flag + Migrations-Marker entfernen,
        // damit up() erneut läuft.
        DB::table('settings')
            ->where('group', 'shift-settings')
            ->where('name', 'granular_defaults_granted')
            ->delete();

        $migration = require base_path(
            'database/settings/2026_07_21_160000_add_granular_defaults_granted_to_shift_settings.php'
        );

        // Der geschützte $this->migrator wird sonst vom SettingsMigrationRunner gesetzt.
        $migrator = app(SettingsMigrator::class);
        (function () use ($migrator): void {
            $this->migrator = $migrator;
        })->call($migration);

        $migration->up();
    }

    private function flagValue(): bool
    {
        $payload = DB::table('settings')
            ->where('group', 'shift-settings')
            ->where('name', 'granular_defaults_granted')
            ->value('payload');

        return json_decode((string) $payload, true) === true;
    }

    #[Test]
    public function it_marks_defaults_as_granted_when_granular_permissions_are_already_enabled(): void
    {
        DB::table('settings')
            ->where('group', 'shift-settings')
            ->where('name', 'granular_permissions_enabled')
            ->update(['payload' => 'true']);

        $this->rerunMigration();

        $this->assertTrue($this->flagValue());
    }

    #[Test]
    public function it_leaves_defaults_ungranted_when_granular_permissions_are_disabled(): void
    {
        DB::table('settings')
            ->where('group', 'shift-settings')
            ->where('name', 'granular_permissions_enabled')
            ->update(['payload' => 'false']);

        $this->rerunMigration();

        $this->assertFalse($this->flagValue());
    }
}
