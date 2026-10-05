<?php

namespace Tests\Feature;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\UploadSettingDefaults;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Database\Seeders\DatabaseSettingsSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Die Migration reset_upload_allowlists_to_defaults ersetzt gespeicherte Endungs-Allowlists
 * (auch "*" oder Listen mit Markup-Typen) durch die Standardwerte aus UploadSettingDefaults.
 * Sie ist idempotent und trifft weder Größenlimits noch andere Einstellungen.
 */
final class UploadAllowlistDefaultsMigrationTest extends FeatureTestCase
{
    private const MIGRATION = 'migrations/2026_09_21_140000_reset_upload_allowlists_to_defaults.php';

    private function storeSetting(string $name, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => UploadSettingDefaults::SETTINGS_GROUP, 'name' => $name],
            ['payload' => json_encode($value), 'locked' => false, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function storedPayload(string $name): mixed
    {
        $payload = DB::table('settings')
            ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
            ->where('name', $name)
            ->value('payload');

        return $payload === null ? null : json_decode($payload, true);
    }

    private function runMigration(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->up();
    }

    #[Test]
    public function migration_replaces_wildcard_and_markup_allowlists_with_defaults(): void
    {
        $this->storeSetting('allowed_project_file_mimetypes', ['*']);
        $this->storeSetting('allowed_room_file_mimetypes', ['pdf', 'html', 'svg']);
        $this->storeSetting('allowed_branding_file_mimetypes', ['*', 'text/html']);
        $this->storeSetting('allowed_contract_file_mimetypes', []);
        $this->storeSetting('allowed_project_file_size', 999);
        $this->storeSetting('business_email', 'buero@example.org');

        $this->runMigration();

        foreach (ArtworkFileTypes::cases() as $type) {
            $this->assertSame(
                UploadSettingDefaults::mimeTypesFor($type),
                $this->storedPayload(UploadSettingDefaults::mimeTypesKey($type)),
                sprintf('Allowlist für "%s" wurde nicht auf den Standard gesetzt', $type->value)
            );
        }

        $this->assertSame(
            UploadSettingDefaults::IMAGE_EXTENSIONS,
            $this->storedPayload('allowed_branding_file_mimetypes')
        );
        $this->assertSame(
            UploadSettingDefaults::DOCUMENT_EXTENSIONS,
            $this->storedPayload('allowed_project_file_mimetypes')
        );

        // Größenlimits und fremde Einstellungen bleiben unangetastet
        $this->assertSame(999, $this->storedPayload('allowed_project_file_size'));
        $this->assertSame('buero@example.org', $this->storedPayload('business_email'));

        // Die Settings-Klasse liest die neuen Werte
        $settings = app(GeneralSettings::class)->refresh();
        $this->assertSame(UploadSettingDefaults::DOCUMENT_EXTENSIONS, $settings->allowed_project_file_mimetypes);
        $this->assertSame(UploadSettingDefaults::IMAGE_EXTENSIONS, $settings->allowed_branding_file_mimetypes);
        $this->assertSame(999, $settings->allowed_project_file_size);
    }

    #[Test]
    public function migration_is_idempotent_and_inserts_missing_keys(): void
    {
        $this->storeSetting('allowed_project_file_mimetypes', ['*']);
        DB::table('settings')
            ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
            ->where('name', 'allowed_room_file_mimetypes')
            ->delete();

        $this->runMigration();

        $firstRun = DB::table('settings')
            ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
            ->where('name', 'like', 'allowed_%_file_mimetypes')
            ->orderBy('name')
            ->get(['name', 'payload', 'updated_at'])
            ->toArray();

        $this->assertCount(count(ArtworkFileTypes::cases()), $firstRun);
        $this->assertSame(
            UploadSettingDefaults::DOCUMENT_EXTENSIONS,
            $this->storedPayload('allowed_room_file_mimetypes')
        );

        $this->travel(1)->minutes();
        $this->runMigration();

        $secondRun = DB::table('settings')
            ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
            ->where('name', 'like', 'allowed_%_file_mimetypes')
            ->orderBy('name')
            ->get(['name', 'payload', 'updated_at'])
            ->toArray();

        $this->assertEquals($firstRun, $secondRun);
    }

    #[Test]
    public function seeder_and_migration_yield_identical_values(): void
    {
        $names = array_keys(UploadSettingDefaults::all());

        DB::table('settings')
            ->where('group', UploadSettingDefaults::SETTINGS_GROUP)
            ->whereIn('name', $names)
            ->delete();
        (new DatabaseSettingsSeeder())->run();

        $seeded = [];
        foreach ($names as $name) {
            $seeded[$name] = $this->storedPayload($name);
        }

        foreach (array_keys(UploadSettingDefaults::mimeTypes()) as $name) {
            $this->storeSetting($name, ['*']);
        }
        $this->runMigration();

        $migrated = [];
        foreach ($names as $name) {
            $migrated[$name] = $this->storedPayload($name);
        }

        $this->assertSame($seeded, $migrated);
        $this->assertSame(UploadSettingDefaults::all(), $migrated);
        $this->assertSame(UploadSettingDefaults::FILE_SIZE_MB, $migrated['allowed_project_file_size']);
    }
}
