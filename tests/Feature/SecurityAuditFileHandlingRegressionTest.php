<?php

namespace Tests\Feature;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sicherheits-Audit 21.09.2026, Abschnitt F (Dateien – Uploads / Downloads / PDF / Export).
 *
 * CRM-Eigenschaftsdateien, Materialausgabe-Anhänge und Ausgabe-PDFs liegen auf der privaten
 * local-Disk und werden nur über autorisierte Routen ausgeliefert; Uploads folgen einer
 * Allowlist; HTML/SVG werden auch bei Wildcard-Einstellung abgelehnt; externe Gäste bekommen
 * Nicht-Bild/PDF-Dateien nie inline; BI-Export-Token sind an die anfragende Person gebunden.
 */
final class SecurityAuditFileHandlingRegressionTest extends FeatureTestCase
{
    private const PNG_1X1_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const HTML = "<!DOCTYPE html>\n<html><body><script>alert(1)</script></body></html>\n";

    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt bereits "local"; die public-Disk kommt für die "liegt NICHT unter
        // /storage"-Nachweise und den Move-Command dazu.
        Storage::fake('public');
    }

    // ---------------------------------------------------------------- Helpers

    /**
     * Laravels Test-Fake rät den MIME-Typ aus dem Dateinamen - genau das Verhalten, das hier
     * geprüft wird, wäre damit unsichtbar. Deshalb echte UploadedFile-Instanzen mit echtem Inhalt.
     */
    private function realUpload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'file-audit-');
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function pngBytes(): string
    {
        return base64_decode(self::PNG_1X1_BASE64);
    }

    private function pdfBytes(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    /**
     * @return array{contact: CrmContact, property: CrmProperty, group: CrmPropertyGroup}
     */
    private function crmUploadProperty(bool $confidential = false): array
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'audit-file-type'],
            ['name' => 'Audit', 'is_system' => false, 'is_active' => true]
        );

        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Audit Contact',
            'is_active' => true,
        ]);

        $group = CrmPropertyGroup::query()->create([
            'name' => 'Audit Files ' . uniqid(),
            'is_confidential' => $confidential,
            'sort_order' => 0,
            'is_system' => false,
        ]);

        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Audit Upload ' . uniqid(),
            'type' => 'upload',
            'is_system' => false,
            'sort_order' => 0,
        ]);

        return compact('contact', 'property', 'group');
    }

    private function jsonHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    private function internalIssuePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Audit Ausgabe',
            'start_date' => '2026-10-01',
            'start_time' => '08:00',
            'end_date' => '2026-10-02',
            'end_time' => '16:00',
        ], $overrides);
    }

    private function externalIssuePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Audit externe Ausgabe',
            'material_value' => 10,
            'issue_date' => '2026-10-01',
            'return_date' => '2026-10-05',
            'external_name' => 'Audit Empfänger',
        ], $overrides);
    }

    // ---------------------------------------------------------------- CRM

    #[Test]
    public function crm_property_file_upload_rejects_html_regardless_of_client_extension(): void
    {
        $this->actingAsUserWith('can view crm');
        ['contact' => $contact, 'property' => $property] = $this->crmUploadProperty();

        foreach (['payload.html', 'payload.png', 'payload.pdf'] as $clientName) {
            $this->post(
                route('crm.contacts.property-file.upload', $contact),
                ['property_id' => $property->id, 'file' => $this->realUpload($clientName, self::HTML)],
                $this->jsonHeaders()
            )->assertStatus(422)->assertJsonValidationErrors(['file']);
        }

        $this->assertSame(0, CrmPropertyValue::query()->where('crm_property_id', $property->id)->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles('crm-property-files'));
    }

    #[Test]
    public function crm_property_file_is_stored_privately_and_downloadable_with_permission(): void
    {
        $this->actingAsUserWith('can view crm');
        ['contact' => $contact, 'property' => $property] = $this->crmUploadProperty();

        $this->post(
            route('crm.contacts.property-file.upload', $contact),
            ['property_id' => $property->id, 'file' => $this->realUpload('vertrag.pdf', $this->pdfBytes())]
        )->assertSessionHasNoErrors();

        $path = (string) CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $property->id)
            ->value('value');

        $this->assertMatchesRegularExpression('#^crm-property-files/[a-f0-9]{32}\.pdf$#', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $this->get(route('crm.contacts.property-file.download', ['crmContact' => $contact, 'property' => $property]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=' . basename($path));

        // Ersetzen räumt die alte Datei weg
        $this->post(
            route('crm.contacts.property-file.upload', $contact),
            ['property_id' => $property->id, 'file' => $this->realUpload('neu.png', $this->pngBytes())]
        )->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);

        // Löschen entfernt Wert und Datei
        $newPath = (string) CrmPropertyValue::query()->where('crm_property_id', $property->id)->value('value');
        Storage::disk('local')->assertExists($newPath);
        $this->delete(route('crm.contacts.property-file.delete', $contact), ['property_id' => $property->id])
            ->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($newPath);
    }

    #[Test]
    public function crm_property_file_download_requires_crm_permission_and_group_visibility(): void
    {
        ['contact' => $contact, 'property' => $property] = $this->crmUploadProperty(confidential: true);
        $path = 'crm-property-files/' . str_repeat('a', 32) . '.pdf';
        Storage::disk('local')->put($path, $this->pdfBytes());
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => $path,
        ]);

        $url = route('crm.contacts.property-file.download', ['crmContact' => $contact, 'property' => $property]);

        // Ohne CRM-Recht: 403 (Route-Middleware)
        $this->actingAs(User::factory()->create());
        $this->get($url)->assertForbidden();

        // Mit CRM-Recht, aber vertrauliche Gruppe ohne Freigabe: 403
        $this->actingAsUserWith('can view crm');
        $this->get($url)->assertForbidden();

        // CRM-Manager sieht alle Gruppen: 200
        $this->actingAsUserWith(['can view crm', 'crm manager']);
        $this->get($url)->assertOk();

        // Die Datei ist weiterhin nicht über /storage erreichbar
        Storage::disk('public')->assertMissing($path);
    }

    // ---------------------------------------------------------------- Materialausgaben

    #[Test]
    public function internal_issue_store_rejects_html_attachment(): void
    {
        $this->actingAsUserWith('inventory.disposition');

        $this->post(
            route('issue-of-material.store'),
            $this->internalIssuePayload(['files' => [$this->realUpload('anhang.html', self::HTML)]]),
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['files.0']);

        $this->assertSame(0, InternalIssue::query()->where('name', 'Audit Ausgabe')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function external_issue_store_rejects_html_attachment(): void
    {
        $this->actingAsUserWith('inventory.disposition');

        $this->post(
            route('extern-issue-of-material.store'),
            $this->externalIssuePayload(['files' => [$this->realUpload('anhang.html', self::HTML)]]),
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['files.0']);

        $this->assertSame(0, ExternalIssue::query()->where('name', 'Audit externe Ausgabe')->count());
    }

    #[Test]
    public function internal_issue_attachment_is_stored_privately(): void
    {
        $this->actingAsUserWith('inventory.disposition');

        $this->post(
            route('issue-of-material.store'),
            $this->internalIssuePayload(['files' => [$this->realUpload('lieferschein.pdf', $this->pdfBytes())]])
        )->assertSessionHasNoErrors();

        $issue = InternalIssue::query()->where('name', 'Audit Ausgabe')->firstOrFail();
        $file = $issue->files()->firstOrFail();

        $this->assertMatchesRegularExpression('#^material-issue/[a-f0-9]{32}\.pdf$#', $file->file_path);
        Storage::disk('local')->assertExists($file->file_path);
        Storage::disk('public')->assertMissing($file->file_path);
        $this->assertSame('lieferschein.pdf', $file->original_name);
    }

    #[Test]
    public function internal_issue_file_download_requires_view_right_and_file_ownership(): void
    {
        $issue = InternalIssue::factory()->create();
        $otherIssue = InternalIssue::factory()->create();
        $path = 'material-issue/' . str_repeat('b', 32) . '.pdf';
        Storage::disk('local')->put($path, $this->pdfBytes());
        $file = InternalIssueFile::query()->create([
            'internal_issue_id' => $issue->id,
            'file_path' => $path,
            'original_name' => 'Ausgabe 1.pdf',
        ]);

        // Ohne Recht: 403
        $this->actingAs(User::factory()->create());
        $this->get(route('issue-of-material.file.download', ['internalIssue' => $issue, 'internalIssueFile' => $file]))
            ->assertForbidden();

        // Mit Dispositionsrecht: 200 als Attachment
        $this->actingAsUserWith('inventory.disposition');
        $this->get(route('issue-of-material.file.download', ['internalIssue' => $issue, 'internalIssueFile' => $file]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="Ausgabe 1.pdf"');

        // Inline nur für Bilder/PDF
        $this->get(route('issue-of-material.file.download', [
            'internalIssue' => $issue,
            'internalIssueFile' => $file,
            'inline' => 1,
        ]))->assertOk()->assertHeader('content-disposition', 'inline; filename="Ausgabe 1.pdf"');

        // Datei einer anderen Ausgabe über deren ID: 404
        $this->get(route('issue-of-material.file.download', [
            'internalIssue' => $otherIssue,
            'internalIssueFile' => $file,
        ]))->assertNotFound();

        // Projektlesende sehen die Dateien ihres Projekts (MaterialIssuePolicy::view)
        $project = Project::factory()->create();
        $issue->update(['project_id' => $project->id]);
        $reader = User::factory()->create();
        $project->users()->attach($reader, ['can_write' => false]);
        $this->actingAs($reader);
        $this->get(route('issue-of-material.file.download', ['internalIssue' => $issue, 'internalIssueFile' => $file]))
            ->assertOk();
    }

    #[Test]
    public function external_issue_file_download_requires_view_right(): void
    {
        $owner = User::factory()->create();
        $issue = ExternalIssue::factory()->create(['issued_by_id' => $owner->id]);
        $path = 'external_material_issues/' . str_repeat('c', 32) . '.png';
        Storage::disk('local')->put($path, $this->pngBytes());
        $file = ExternalIssueFile::query()->create([
            'external_issue_id' => $issue->id,
            'file_path' => $path,
            'original_name' => 'foto.png',
        ]);
        $url = route('extern-issue-of-material.file.download', ['externalIssue' => $issue, 'externalIssueFile' => $file]);

        $this->actingAs(User::factory()->create());
        $this->get($url)->assertForbidden();

        $this->actingAs($owner);
        $this->get($url)->assertOk()->assertHeader('content-disposition', 'attachment; filename=foto.png');

        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function issue_html_attachment_is_never_rendered_inline_even_from_legacy_public_disk(): void
    {
        // Altbestand vor dem Move-Command: Datei liegt noch auf public; die Route liefert sie
        // aus, aber nie inline.
        $issue = InternalIssue::factory()->create();
        $path = 'material-issue/' . str_repeat('d', 32) . '.bin';
        Storage::disk('public')->put($path, self::HTML);
        $file = InternalIssueFile::query()->create([
            'internal_issue_id' => $issue->id,
            'file_path' => $path,
            'original_name' => 'alt.html',
        ]);

        $this->actingAsUserWith('inventory.disposition');
        $response = $this->get(route('issue-of-material.file.download', [
            'internalIssue' => $issue,
            'internalIssueFile' => $file,
            'inline' => 1,
        ]));

        $response->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('content-disposition'));
    }

    // ---------------------------------------------------------------- Move-Command

    #[Test]
    public function move_command_moves_public_files_to_local_and_normalises_paths(): void
    {
        $issue = InternalIssue::factory()->create();
        $issuePath = 'material-issue/' . str_repeat('e', 32) . '.pdf';
        Storage::disk('public')->put($issuePath, $this->pdfBytes());
        $issueFile = InternalIssueFile::query()->create([
            'internal_issue_id' => $issue->id,
            'file_path' => $issuePath,
            'original_name' => 'alt.pdf',
        ]);

        $externalIssue = ExternalIssue::factory()->create();
        $externalPath = 'external_material_issues/' . str_repeat('f', 32) . '.pdf';
        Storage::disk('public')->put($externalPath, $this->pdfBytes());
        $externalFile = ExternalIssueFile::query()->create([
            'external_issue_id' => $externalIssue->id,
            'file_path' => $externalPath,
            'original_name' => 'alt-extern.pdf',
        ]);

        ['contact' => $contact, 'property' => $property] = $this->crmUploadProperty();
        $crmPath = 'crm-property-files/' . str_repeat('0', 40) . '.pdf';
        Storage::disk('public')->put($crmPath, $this->pdfBytes());
        $crmValue = CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            // Präfix-Variante aus dem Altbestand wird normalisiert
            'value' => '/storage/' . $crmPath,
        ]);

        // Bereits private Datei bleibt unangetastet, der public-Rest wird entfernt
        $alreadyPrivate = 'material-issue/' . str_repeat('9', 32) . '.pdf';
        Storage::disk('local')->put($alreadyPrivate, 'private');
        Storage::disk('public')->put($alreadyPrivate, 'stale copy');
        InternalIssueFile::query()->create([
            'internal_issue_id' => $issue->id,
            'file_path' => $alreadyPrivate,
            'original_name' => 'schon-privat.pdf',
        ]);

        $this->artisan('artwork:security:move-public-files')->assertSuccessful();

        foreach ([$issuePath, $externalPath, $crmPath, $alreadyPrivate] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertSame('private', Storage::disk('local')->get($alreadyPrivate));
        $this->assertSame($issuePath, $issueFile->fresh()->file_path);
        $this->assertSame($externalPath, $externalFile->fresh()->file_path);
        $this->assertSame($crmPath, $crmValue->fresh()->value);

        // Idempotent: zweiter Lauf ändert nichts und meldet keinen Fehler
        $this->artisan('artwork:security:move-public-files')->assertSuccessful();
        Storage::disk('local')->assertExists($crmPath);
        $this->assertSame($crmPath, $crmValue->fresh()->value);

        // Verschobene CRM-Datei ist über die autorisierte Route erreichbar
        $this->actingAsUserWith('can view crm');
        $this->get(route('crm.contacts.property-file.download', ['crmContact' => $contact, 'property' => $property]))
            ->assertOk();
    }

    // ---------------------------------------------------------------- Externe Gäste

    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab}
     */
    private function externalContext(): array
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->enabled = true;
        $settings->save();

        $external = ExternalAccess::factory()->active()->create();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);
        $component = Component::create(['name' => 'Dokumente', 'type' => 'ProjectDocumentsComponent', 'data' => []]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 0,
            'scope' => [$tab->id],
        ]);
        $this->actingAs($external, 'external');

        return compact('external', 'project', 'tab');
    }

    #[Test]
    public function external_guest_inline_request_serves_html_as_attachment_and_images_inline(): void
    {
        ['project' => $project, 'tab' => $tab] = $this->externalContext();

        $htmlBasename = str_repeat('1', 32) . '.bin';
        Storage::disk('local')->put('project_files/' . $htmlBasename, self::HTML);
        $htmlFile = ProjectFile::query()->create([
            'project_id' => $project->id,
            'tab_id' => $tab->id,
            'name' => 'seite.html',
            'basename' => $htmlBasename,
        ]);

        $pngBasename = str_repeat('2', 32) . '.png';
        Storage::disk('local')->put('project_files/' . $pngBasename, $this->pngBytes());
        $pngFile = ProjectFile::query()->create([
            'project_id' => $project->id,
            'tab_id' => $tab->id,
            'name' => 'bild.png',
            'basename' => $pngBasename,
        ]);

        $htmlResponse = $this->get(route('external.project.tab.documents.download', [
            'project' => $project->id,
            'tab' => $tab->id,
            'file' => $htmlFile->id,
            'inline' => 1,
        ]));
        $htmlResponse->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $htmlResponse->headers->get('content-disposition'));

        $pngResponse = $this->get(route('external.project.tab.documents.download', [
            'project' => $project->id,
            'tab' => $tab->id,
            'file' => $pngFile->id,
            'inline' => 1,
        ]));
        $pngResponse->assertOk();
        $this->assertStringStartsWith('inline;', (string) $pngResponse->headers->get('content-disposition'));
    }

    // ---------------------------------------------------------------- Allowlist-Defaults / Wildcard

    #[Test]
    public function project_file_upload_hard_denies_html_even_with_wildcard_setting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['*'];
        $settings->allowed_project_file_size = 10;
        $settings->save();

        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('seite.html', self::HTML), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertStatus(422);

        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('grafik.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertStatus(422);

        // Server-seitig ausführbar / Webserver-Konfiguration: Endung reicht, Inhalt egal
        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('archive.phar', "<?php echo 1;\n"), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('.htaccess', "AddType application/x-httpd-php .png\n"), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['file']);

        // Polyglot: gültiges PNG (finfo: image/png), Client-Name ".php" -> abgelehnt, nicht umbenannt
        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('bild.php', $this->pngBytes() . '<?php system($_GET["c"]); ?>'), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->assertSame(0, ProjectFile::query()->where('project_id', $project->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles('project_files'));

        // Echtes PNG wird gespeichert - unter Hash-Namen mit Endung des erkannten Inhalts
        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('bild.png', $this->pngBytes()), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertSuccessful();
        $pngFile = ProjectFile::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertStringEndsWith('.png', $pngFile->basename);
        Storage::disk('local')->assertExists('project_files/' . $pngFile->basename);

        // Wildcard lässt reguläre Dokumente weiterhin durch
        $this->post(
            route('project_files.store', $project),
            ['file' => $this->realUpload('doku.pdf', $this->pdfBytes()), 'tabId' => $tab->id],
            $this->jsonHeaders()
        )->assertSuccessful();
        $this->assertSame(2, ProjectFile::query()->where('project_id', $project->id)->count());
    }

    #[Test]
    public function project_file_replacement_runs_upload_rules(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->save();

        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $basename = str_repeat('3', 32) . '.pdf';
        Storage::disk('local')->put('project_files/' . $basename, $this->pdfBytes());
        $file = ProjectFile::query()->create([
            'project_id' => $project->id,
            'tab_id' => ProjectTab::factory()->create()->id,
            'name' => 'alt.pdf',
            'basename' => $basename,
        ]);

        $this->post(
            route('project_files.update', $file),
            ['file' => $this->realUpload('neu.png', $this->pngBytes())],
            $this->jsonHeaders()
        )->assertStatus(422);

        $this->assertSame($basename, $file->fresh()->basename);
        Storage::disk('local')->assertExists('project_files/' . $basename);
    }

    #[Test]
    public function money_source_file_upload_requires_file_and_follows_allowlist(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->save();

        $this->actingAsAdmin();
        $moneySource = MoneySource::factory()->create();

        // Ohne Datei: Validierungsfehler statt 500
        $this->post(route('money_sources_files.store', $moneySource), [], $this->jsonHeaders())
            ->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->post(
            route('money_sources_files.store', $moneySource),
            ['file' => $this->realUpload('x.html', self::HTML)],
            $this->jsonHeaders()
        )->assertStatus(422);

        $this->post(
            route('money_sources_files.store', $moneySource),
            ['file' => $this->realUpload('antrag.pdf', $this->pdfBytes())]
        )->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, $moneySource->moneySourceFiles()->count());
    }

    #[Test]
    public function branding_upload_rejects_svg_and_html(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_branding_file_mimetypes = ['*'];
        $settings->allowed_branding_file_size = 10;
        $settings->save();

        $this->actingAsAdmin();

        $this->put(
            route('tool.branding.update'),
            ['smallLogo' => $this->realUpload('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['smallLogo']);

        $this->put(
            route('tool.branding.update'),
            ['banner' => $this->realUpload('banner.png', self::HTML)],
            $this->jsonHeaders()
        )->assertStatus(422)->assertJsonValidationErrors(['banner']);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    // ---------------------------------------------------------------- BI-Export-Token

    #[Test]
    public function bi_export_status_and_download_are_bound_to_the_requesting_user(): void
    {
        $owner = $this->actingAsUserWith('can export bi data');
        $token = 'audit-token-' . str_repeat('a', 20);
        Cache::put('bi_export_status_' . $token, ['status' => 'pending', 'user_id' => $owner->id], now()->addHour());

        $this->getJson(route('bi.export.status', $token))->assertOk()->assertJsonPath('status', 'pending')
            ->assertJsonMissingPath('user_id');

        $this->actingAsUserWith('can export bi data');
        $this->getJson(route('bi.export.status', $token))->assertForbidden();
        $this->get(route('bi.export.download', $token))->assertForbidden();

        // Token-Format wird an der Route erzwungen
        $this->getJson('/bi/export/status/' . rawurlencode('../x'))->assertNotFound();
        $this->getJson('/bi/export/status/short')->assertNotFound();
    }
}
