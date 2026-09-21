<?php

namespace Tests\Feature;

use Artwork\Core\Console\Commands\UpdateArtwork;
use Artwork\Core\FileHandling\StoredFilePath;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sicherheits-Audit 21.09.2026, Abschnitt F - Kompatibilität für bestehende Installationen.
 *
 * Vor dem Audit lagen CRM-Eigenschaftsdateien, Materialausgabe-Anhänge und Ausgabe-PDFs auf der
 * public-Disk (/storage/...). Nach dem Deploy sind die alten /storage-Links tot - die Dateien selbst
 * müssen aber in JEDEM Zustand über die neuen Download-Routen erreichbar bleiben:
 *   (a) vor dem Lauf von artwork:security:move-public-files (Datei noch auf public, DB-Pfad alt),
 *   (b) nach dem Lauf (Datei auf local, DB-Pfad normalisiert),
 *   (c) DB-Pfad mit "/storage/"-Präfix oder als absolute URL gespeichert.
 */
final class SecurityAuditFileMigrationCompatTest extends FeatureTestCase
{
    private const PNG_1X1_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const MOVE_COMMAND = 'artwork:security:move-public-files';

    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt "local"; "public" steht hier für den Altbestand vor dem Deploy.
        Storage::fake('public');
    }

    // ---------------------------------------------------------------- Helpers

    private function pngBytes(): string
    {
        return base64_decode(self::PNG_1X1_BASE64);
    }

    private function pdfBytes(string $marker = 'alt'): string
    {
        return "%PDF-1.4\n% " . $marker . "\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    /**
     * Laravels altes store('crm-property-files', 'public') erzeugte 40-stellige alphanumerische Namen.
     */
    private function legacyCrmPath(string $extension = 'pdf'): string
    {
        return 'crm-property-files/' . substr(str_repeat('Ab3', 14), 0, 40) . '.' . $extension;
    }

    /**
     * @return array{contact: CrmContact, property: CrmProperty, value: CrmPropertyValue}
     */
    private function crmUploadWithValue(string $storedValue): array
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'compat-file-type'],
            ['name' => 'Compat', 'is_system' => false, 'is_active' => true]
        );

        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Compat Contact',
            'is_active' => true,
        ]);

        $group = CrmPropertyGroup::query()->create([
            'name' => 'Compat Files ' . uniqid(),
            'is_confidential' => false,
            'sort_order' => 0,
            'is_system' => false,
        ]);

        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Compat Upload ' . uniqid(),
            'type' => 'upload',
            'is_system' => false,
            'sort_order' => 0,
        ]);

        $value = CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => $storedValue,
        ]);

        return compact('contact', 'property', 'value');
    }

    private function crmDownloadUrl(CrmContact $contact, CrmProperty $property, bool $inline = false): string
    {
        return route('crm.contacts.property-file.download', array_filter([
            'crmContact' => $contact->id,
            'property' => $property->id,
            'inline' => $inline ? 1 : null,
        ]));
    }

    private function internalFile(InternalIssue $issue, string $storedPath, string $originalName): InternalIssueFile
    {
        return InternalIssueFile::query()->create([
            'internal_issue_id' => $issue->id,
            'file_path' => $storedPath,
            'original_name' => $originalName,
        ]);
    }

    private function externalFile(ExternalIssue $issue, string $storedPath, string $originalName): ExternalIssueFile
    {
        return ExternalIssueFile::query()->create([
            'external_issue_id' => $issue->id,
            'file_path' => $storedPath,
            'original_name' => $originalName,
        ]);
    }

    private function internalDownloadUrl(InternalIssue $issue, InternalIssueFile $file, bool $inline = false): string
    {
        return route('issue-of-material.file.download', array_filter([
            'internalIssue' => $issue->id,
            'internalIssueFile' => $file->id,
            'inline' => $inline ? 1 : null,
        ]));
    }

    private function externalDownloadUrl(ExternalIssue $issue, ExternalIssueFile $file, bool $inline = false): string
    {
        return route('extern-issue-of-material.file.download', array_filter([
            'externalIssue' => $issue->id,
            'externalIssueFile' => $file->id,
            'inline' => $inline ? 1 : null,
        ]));
    }

    private function assertDownloadDelivers(string $url, string $expectedContent, ?string $disposition = null): void
    {
        $response = $this->get($url)->assertOk();

        if ($disposition !== null) {
            $response->assertHeader('content-disposition', $disposition);
        }

        $this->assertSame($expectedContent, $response->streamedContent());
    }

    // ---------------------------------------------------------------- Normalisierung

    #[Test]
    public function stored_file_path_normalises_all_legacy_prefix_variants(): void
    {
        $expected = 'crm-property-files/abc.pdf';

        $variants = [
            'crm-property-files/abc.pdf',
            '/crm-property-files/abc.pdf',
            'storage/crm-property-files/abc.pdf',
            '/storage/crm-property-files/abc.pdf',
            'public/crm-property-files/abc.pdf',
            'https://artwork.example.test/storage/crm-property-files/abc.pdf',
            'http://artwork.example.test/crm-property-files/abc.pdf',
            "  /storage/crm-property-files/abc.pdf \n",
            'storage\\crm-property-files\\abc.pdf',
        ];

        foreach ($variants as $raw) {
            $this->assertSame($expected, StoredFilePath::normalise($raw), 'Variante: ' . $raw);
        }

        $this->assertNull(StoredFilePath::normalise(''));
        $this->assertNull(StoredFilePath::normalise('   '));
        $this->assertNull(StoredFilePath::normalise(null));
        $this->assertNull(StoredFilePath::normalise('/storage/../.env'));
        $this->assertNull(StoredFilePath::normalise("crm-property-files/a\0b.pdf"));

        $this->assertSame(
            'material-issue/x.pdf',
            StoredFilePath::normaliseWithin('/storage/material-issue/x.pdf', ['material-issue', 'materialausgabe'])
        );
        $this->assertNull(StoredFilePath::normaliseWithin('/storage/profile-photos/x.png', ['material-issue']));
    }

    // ---------------------------------------------------------------- CRM

    #[Test]
    public function crm_property_file_from_public_disk_is_downloadable_before_and_after_move(): void
    {
        $this->actingAsUserWith('can view crm');
        $path = $this->legacyCrmPath();
        $content = $this->pdfBytes('crm-legacy');
        Storage::disk('public')->put($path, $content);
        ['contact' => $contact, 'property' => $property, 'value' => $value] = $this->crmUploadWithValue($path);

        // (a) vor dem Move-Command: Fallback auf public
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($contact, $property),
            $content,
            'attachment; filename=' . basename($path)
        );
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($contact, $property, true),
            $content,
            'inline; filename=' . basename($path)
        );

        // (b) nach dem Move-Command: Datei privat, Pfad unverändert, weiterhin erreichbar
        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame($path, $value->fresh()->value);
        $this->assertDownloadDelivers($this->crmDownloadUrl($contact, $property), $content);
    }

    #[Test]
    public function crm_property_file_with_storage_prefix_or_absolute_url_stays_downloadable(): void
    {
        $this->actingAsUserWith('can view crm');

        $prefixedPath = $this->legacyCrmPath('pdf');
        $prefixedContent = $this->pdfBytes('crm-prefixed');
        Storage::disk('public')->put($prefixedPath, $prefixedContent);
        $prefixed = $this->crmUploadWithValue('/storage/' . $prefixedPath);

        $absolutePath = 'crm-property-files/' . str_repeat('Z9', 20) . '.png';
        $absoluteContent = $this->pngBytes();
        Storage::disk('public')->put($absolutePath, $absoluteContent);
        $absolute = $this->crmUploadWithValue('https://artwork.example.test/storage/' . $absolutePath);

        // (c) vor dem Move-Command, Pfad mit Präfix / als URL
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($prefixed['contact'], $prefixed['property']),
            $prefixedContent
        );
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($absolute['contact'], $absolute['property'], true),
            $absoluteContent,
            'inline; filename=' . basename($absolutePath)
        );

        // nach dem Move-Command: Pfade normalisiert, Dateien privat, weiterhin erreichbar
        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        $this->assertSame($prefixedPath, $prefixed['value']->fresh()->value);
        $this->assertSame($absolutePath, $absolute['value']->fresh()->value);
        Storage::disk('local')->assertExists($prefixedPath);
        Storage::disk('local')->assertExists($absolutePath);
        Storage::disk('public')->assertMissing($prefixedPath);
        Storage::disk('public')->assertMissing($absolutePath);
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($prefixed['contact'], $prefixed['property']),
            $prefixedContent
        );
        $this->assertDownloadDelivers(
            $this->crmDownloadUrl($absolute['contact'], $absolute['property']),
            $absoluteContent
        );
    }

    #[Test]
    public function deleting_a_crm_property_file_with_storage_prefix_removes_it_from_the_public_disk(): void
    {
        $this->actingAsUserWith(['can view crm', 'crm manager']);
        $path = $this->legacyCrmPath();
        Storage::disk('public')->put($path, $this->pdfBytes());
        ['contact' => $contact, 'property' => $property] = $this->crmUploadWithValue('/storage/' . $path);

        $this->delete(route('crm.contacts.property-file.delete', $contact), ['property_id' => $property->id])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(
            CrmPropertyValue::query()->where('crm_property_id', $property->id)->value('value')
        );
    }

    // ---------------------------------------------------------------- Materialausgabe (intern)

    #[Test]
    public function internal_issue_attachment_and_pdf_from_public_disk_stay_downloadable(): void
    {
        $this->actingAsUserWith('inventory.disposition');
        $issue = InternalIssue::factory()->create();

        $imagePath = 'material-issue/' . str_repeat('a', 32) . '.png';
        $imageContent = $this->pngBytes();
        Storage::disk('public')->put($imagePath, $imageContent);
        $image = $this->internalFile($issue, $imagePath, 'foto.png');

        $pdfPath = 'material-issue/' . str_repeat('b', 32) . '.pdf';
        $pdfContent = $this->pdfBytes('int-pdf');
        Storage::disk('public')->put($pdfPath, $pdfContent);
        $pdf = $this->internalFile($issue, $pdfPath, 'int._Materialausgabe_Nr._1_2026-01-01.pdf');

        // (a) vor dem Move-Command: Bild-Vorschau inline (Galerie/Thumbnail), PDF als Download
        $this->assertDownloadDelivers(
            $this->internalDownloadUrl($issue, $image, true),
            $imageContent,
            'inline; filename=foto.png'
        );
        $this->assertDownloadDelivers(
            $this->internalDownloadUrl($issue, $pdf),
            $pdfContent,
            'attachment; filename=int._Materialausgabe_Nr._1_2026-01-01.pdf'
        );

        // (b) nach dem Move-Command
        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        foreach ([$imagePath, $pdfPath] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertSame($imagePath, $image->fresh()->file_path);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $image, true), $imageContent);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $pdf), $pdfContent);
    }

    #[Test]
    public function internal_issue_file_with_storage_prefix_is_served_and_normalised(): void
    {
        $this->actingAsUserWith('inventory.disposition');
        $issue = InternalIssue::factory()->create();
        $path = 'material-issue/' . str_repeat('c', 32) . '.pdf';
        $content = $this->pdfBytes('int-prefixed');
        Storage::disk('public')->put($path, $content);
        $file = $this->internalFile($issue, '/storage/' . $path, 'lieferschein.pdf');

        // (c) vor dem Move-Command mit Präfix
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $file), $content);

        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        $this->assertSame($path, $file->fresh()->file_path);
        Storage::disk('local')->assertExists($path);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $file), $content);

        // Löschen über die Route räumt auch nach Normalisierung sauber weg
        $this->delete(route('issue-of-material.file.delete', $file))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertNull(InternalIssueFile::query()->find($file->id));
    }

    #[Test]
    public function internal_issue_file_in_legacy_materialausgabe_directory_is_moved_too(): void
    {
        $this->actingAsUserWith('inventory.disposition');
        $issue = InternalIssue::factory()->create();
        $path = 'materialausgabe/' . str_repeat('d', 40) . '.pdf';
        $content = $this->pdfBytes('legacy-dir');
        Storage::disk('public')->put($path, $content);
        $file = $this->internalFile($issue, $path, 'alt.pdf');

        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $file), $content);

        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $file), $content);
    }

    // ---------------------------------------------------------------- Materialausgabe (extern)

    #[Test]
    public function external_issue_attachment_from_public_disk_stays_downloadable_in_every_state(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $issue = ExternalIssue::factory()->create(['issued_by_id' => $owner->id]);

        $plainPath = 'external_material_issues/' . str_repeat('e', 32) . '.png';
        $plainContent = $this->pngBytes();
        Storage::disk('public')->put($plainPath, $plainContent);
        $plain = $this->externalFile($issue, $plainPath, 'foto.png');

        $prefixedPath = 'external_material_issues/' . str_repeat('f', 32) . '.pdf';
        $prefixedContent = $this->pdfBytes('ext-prefixed');
        Storage::disk('public')->put($prefixedPath, $prefixedContent);
        $prefixed = $this->externalFile($issue, '/storage/' . $prefixedPath, 'ext._Materialausgabe.pdf');

        // (a) + (c) vor dem Move-Command
        $this->assertDownloadDelivers(
            $this->externalDownloadUrl($issue, $plain, true),
            $plainContent,
            'inline; filename=foto.png'
        );
        $this->assertDownloadDelivers($this->externalDownloadUrl($issue, $prefixed), $prefixedContent);

        // (b) nach dem Move-Command
        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();
        $this->assertSame($prefixedPath, $prefixed->fresh()->file_path);
        foreach ([$plainPath, $prefixedPath] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertDownloadDelivers($this->externalDownloadUrl($issue, $plain, true), $plainContent);
        $this->assertDownloadDelivers($this->externalDownloadUrl($issue, $prefixed), $prefixedContent);
    }

    // ---------------------------------------------------------------- Move-Command: Robustheit

    #[Test]
    public function move_command_reports_missing_files_without_failing_and_still_normalises_their_paths(): void
    {
        $issue = InternalIssue::factory()->create();
        $missingPath = 'material-issue/' . str_repeat('1', 32) . '.pdf';
        $missing = $this->internalFile($issue, '/storage/' . $missingPath, 'verschollen.pdf');

        $presentPath = 'material-issue/' . str_repeat('2', 32) . '.pdf';
        Storage::disk('public')->put($presentPath, $this->pdfBytes('present'));
        $present = $this->internalFile($issue, $presentPath, 'da.pdf');

        $this->artisan(self::MOVE_COMMAND)
            ->expectsOutputToContain('File missing on both disks: ' . $missingPath)
            ->expectsOutputToContain('Moved: 1, already private: 0, paths normalised: 1, missing on both disks: 1')
            ->assertSuccessful();

        $this->assertSame($missingPath, $missing->fresh()->file_path);
        Storage::disk('local')->assertExists($present->fresh()->file_path);

        // Zweiter Lauf: nichts mehr zu tun, weiterhin kein Fehler
        $this->artisan(self::MOVE_COMMAND)
            ->expectsOutputToContain('Moved: 0, already private: 1, paths normalised: 0, missing on both disks: 1')
            ->assertSuccessful();
    }

    #[Test]
    public function move_command_skips_a_file_it_cannot_write_and_keeps_it_served_from_public(): void
    {
        $this->actingAsUserWith('inventory.disposition');
        $issue = InternalIssue::factory()->create();

        // Zielpfad auf "local" ist durch ein Verzeichnis blockiert → Schreiben schlägt fehl
        $blockedPath = 'material-issue/' . str_repeat('3', 32) . '.pdf';
        $blockedContent = $this->pdfBytes('blocked');
        Storage::disk('public')->put($blockedPath, $blockedContent);
        Storage::disk('local')->makeDirectory($blockedPath);
        $blocked = $this->internalFile($issue, $blockedPath, 'blockiert.pdf');

        $okPath = 'material-issue/' . str_repeat('4', 32) . '.pdf';
        $okContent = $this->pdfBytes('ok');
        Storage::disk('public')->put($okPath, $okContent);
        $ok = $this->internalFile($issue, $okPath, 'ok.pdf');

        $this->artisan(self::MOVE_COMMAND)
            ->expectsOutputToContain('failed: 1')
            ->assertSuccessful();

        // Der Rest wurde verschoben ...
        Storage::disk('local')->assertExists($okPath);
        Storage::disk('public')->assertMissing($okPath);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $ok), $okContent);

        // ... die problematische Datei liegt weiter auf public und wird von dort ausgeliefert
        Storage::disk('public')->assertExists($blockedPath);
        $this->assertDownloadDelivers($this->internalDownloadUrl($issue, $blocked), $blockedContent);
    }

    #[Test]
    public function move_command_dry_run_changes_nothing(): void
    {
        $issue = InternalIssue::factory()->create();
        $path = 'material-issue/' . str_repeat('5', 32) . '.pdf';
        Storage::disk('public')->put($path, $this->pdfBytes());
        $file = $this->internalFile($issue, '/storage/' . $path, 'dry.pdf');

        $this->artisan(self::MOVE_COMMAND, ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run] Moved: 1')
            ->assertSuccessful();

        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame('/storage/' . $path, $file->fresh()->file_path);
    }

    #[Test]
    public function move_command_ignores_paths_outside_the_expected_directories(): void
    {
        $issue = InternalIssue::factory()->create();
        Storage::disk('public')->put('profile-photos/fremd.png', $this->pngBytes());
        $foreign = $this->internalFile($issue, '/storage/profile-photos/fremd.png', 'fremd.png');
        $traversal = $this->internalFile($issue, 'material-issue/../.env', 'env');

        $this->artisan(self::MOVE_COMMAND)->assertSuccessful();

        Storage::disk('public')->assertExists('profile-photos/fremd.png');
        Storage::disk('local')->assertMissing('profile-photos/fremd.png');
        $this->assertSame('/storage/profile-photos/fremd.png', $foreign->fresh()->file_path);
        $this->assertSame('material-issue/../.env', $traversal->fresh()->file_path);
    }

    #[Test]
    public function artwork_update_runs_the_move_command(): void
    {
        $source = (string) file_get_contents((new \ReflectionClass(UpdateArtwork::class))->getFileName());

        $this->assertStringContainsString("\$this->call('" . self::MOVE_COMMAND . "')", $source);
        $this->assertStringContainsString('$this->movePublicFilesToPrivateDisk()', $source);
    }
}
