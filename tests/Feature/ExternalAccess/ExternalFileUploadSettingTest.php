<?php

namespace Tests\Feature\ExternalAccess;

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalProjectTabController;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Events\DeleteDocumentInProject;
use Artwork\Modules\Project\Events\UploadNewDocumentInProject;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * Einstellung "Dateiupload für Externe erlauben" (Einstellungen → Externer Zugriff): Upload und Löschen
 * eigener Uploads durch externe Zugänge nur bei aktivem Schalter, Download/Anzeige bleiben unverändert.
 */
final class ExternalFileUploadSettingTest extends ExternalAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        Event::fake([UploadNewDocumentInProject::class, DeleteDocumentInProject::class]);

        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->save();
    }

    private function setUploadEnabled(bool $enabled): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->file_upload_enabled = $enabled;
        $settings->save();
    }

    private function uploadEnabled(): bool
    {
        return app(ExternalAccessSettings::class)->refresh()->file_upload_enabled;
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => true,
            'expiry_reminder_days' => 3,
            'company_name_override' => '',
            'default_crm_access_months' => 12,
            'default_tab_access_days' => 90,
            'login_token_lifetime_minutes' => 15,
            'session_idle_timeout_minutes' => 120,
            'session_absolute_lifetime_minutes' => 480,
            'rate_limit_request_link_per_email_per_hour' => 3,
            'rate_limit_request_link_per_ip_per_hour' => 10,
        ], $overrides);
    }

    /**
     * Echte UploadedFile-Instanz: der Test-Fake rät den MIME-Typ aus dem Dateinamen.
     */
    private function realPdfUpload(string $clientName = 'rider.pdf'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ext-upload-');
        file_put_contents(
            $path,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n"
            . "<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n"
        );

        return new UploadedFile($path, $clientName, null, null, true);
    }

    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab, component: Component, scope: ExternalAccessScope}
     */
    private function context(): array
    {
        $external = ExternalAccess::factory()->active()->create();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $scope = ExternalAccessScope::factory()->write()->create([
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

        return compact('external', 'project', 'tab', 'component', 'scope');
    }

    private function storeParams(array $ctx): array
    {
        return ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'component' => $ctx['component']->id];
    }

    private function fileParams(array $ctx, ProjectFile $file): array
    {
        return ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'file' => $file->id];
    }

    private function ownFile(array $ctx): ProjectFile
    {
        $basename = str_repeat('a', 32) . '.pdf';
        Storage::put('project_files/' . $basename, '%PDF-1.4 test');

        return $ctx['project']->project_files()->create([
            'tab_id' => $ctx['tab']->id,
            'name' => 'eigen.pdf',
            'basename' => $basename,
            'external_access_id' => $ctx['external']->id,
        ]);
    }

    /**
     * Rendert die Tab-Seite direkt über den Controller (Scope wie von der Middleware gesetzt).
     */
    private function tabProps(array $ctx): array
    {
        $controller = $this->app->make(ExternalProjectTabController::class);
        $request = Request::create("/external/projects/{$ctx['project']->id}/tabs/{$ctx['tab']->id}", 'GET');
        $request->attributes->set('external_scope', $ctx['scope']);

        $response = $controller->show($request, $ctx['project'], $ctx['tab']);

        return $response->toResponse($request)->getOriginalContent()->getData()['page']['props'];
    }

    // ---------------------------------------------------------------- Default (aus)

    #[Test]
    public function setting_is_off_by_default(): void
    {
        $this->assertFalse($this->uploadEnabled());
    }

    #[Test]
    public function upload_is_forbidden_and_prop_false_while_setting_is_off(): void
    {
        $ctx = $this->context();

        $this->postJson(route('external.project.tab.documents.store', $this->storeParams($ctx)), [
            'file' => $this->realPdfUpload(),
        ])->assertForbidden();
        $this->assertSame(0, ProjectFile::query()->where('project_id', $ctx['project']->id)->count());

        $props = $this->tabProps($ctx);
        $this->assertArrayHasKey('externalFileUploadEnabled', $props);
        $this->assertFalse($props['externalFileUploadEnabled']);
    }

    #[Test]
    public function upload_without_file_is_forbidden_not_unprocessable_while_setting_is_off(): void
    {
        $ctx = $this->context();

        $this->postJson(route('external.project.tab.documents.store', $this->storeParams($ctx)), [])
            ->assertForbidden();
    }

    #[Test]
    public function deleting_own_upload_is_forbidden_while_setting_is_off(): void
    {
        $ctx = $this->context();
        $file = $this->ownFile($ctx);

        $this->deleteJson(route('external.project.tab.documents.destroy', $this->fileParams($ctx, $file)))
            ->assertForbidden();
        $this->assertDatabaseHas('project_files', ['id' => $file->id, 'deleted_at' => null]);
    }

    #[Test]
    public function listing_and_download_keep_working_while_setting_is_off(): void
    {
        $ctx = $this->context();
        $file = $this->ownFile($ctx);

        $this->getJson(route('external.project.tab.documents.index', $this->storeParams($ctx)))
            ->assertOk()
            ->assertJsonCount(1, 'documents');

        $this->get(route('external.project.tab.documents.download', $this->fileParams($ctx, $file)))
            ->assertOk();
    }

    // ---------------------------------------------------------------- Schalter an

    #[Test]
    public function upload_and_delete_work_and_prop_is_true_while_setting_is_on(): void
    {
        $this->setUploadEnabled(true);
        $ctx = $this->context();

        $this->postJson(route('external.project.tab.documents.store', $this->storeParams($ctx)), [
            'file' => $this->realPdfUpload(),
        ])
            ->assertCreated()
            ->assertJsonPath('document.name', 'rider.pdf')
            ->assertJsonPath('document.uploaded_by_me', true);

        $file = ProjectFile::query()->where('project_id', $ctx['project']->id)->firstOrFail();
        $this->assertSame($ctx['external']->id, (int) $file->external_access_id);
        Storage::assertExists('project_files/' . $file->basename);

        $this->deleteJson(route('external.project.tab.documents.destroy', $this->fileParams($ctx, $file)))
            ->assertOk();
        $this->assertSoftDeleted('project_files', ['id' => $file->id]);

        $this->assertTrue($this->tabProps($ctx)['externalFileUploadEnabled']);
    }

    #[Test]
    public function switching_off_afterwards_blocks_deleting_earlier_uploads(): void
    {
        $this->setUploadEnabled(true);
        $ctx = $this->context();

        $this->postJson(route('external.project.tab.documents.store', $this->storeParams($ctx)), [
            'file' => $this->realPdfUpload(),
        ])->assertCreated();
        $file = ProjectFile::query()->where('project_id', $ctx['project']->id)->firstOrFail();

        $this->setUploadEnabled(false);

        $this->deleteJson(route('external.project.tab.documents.destroy', $this->fileParams($ctx, $file)))
            ->assertForbidden();
        $this->assertDatabaseHas('project_files', ['id' => $file->id, 'deleted_at' => null]);
        $this->get(route('external.project.tab.documents.download', $this->fileParams($ctx, $file)))
            ->assertOk();
    }

    // ---------------------------------------------------------------- Einstellungen → Externer Zugriff

    #[Test]
    public function settings_update_requires_admin_role(): void
    {
        $this->actingAsUserWith([PermissionEnum::SETTINGS_UPDATE]);

        $this->patch(
            route('settings.external-access.update'),
            $this->settingsPayload(['file_upload_enabled' => true]),
        )->assertForbidden();

        $this->assertFalse($this->uploadEnabled());
    }

    #[Test]
    public function admin_can_switch_the_flag_on_and_off(): void
    {
        $this->actingAsAdmin();

        $this->patch(
            route('settings.external-access.update'),
            $this->settingsPayload(['file_upload_enabled' => true]),
        )->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertTrue($this->uploadEnabled());

        $this->patch(
            route('settings.external-access.update'),
            $this->settingsPayload(['file_upload_enabled' => false]),
        )->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertFalse($this->uploadEnabled());
    }

    #[Test]
    public function missing_key_resets_the_flag_to_off(): void
    {
        $this->actingAsAdmin();
        $this->setUploadEnabled(true);

        $this->patch(route('settings.external-access.update'), $this->settingsPayload())
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->uploadEnabled());
    }

    #[Test]
    public function file_settings_route_ignores_the_flag(): void
    {
        $this->actingAsUserWith([PermissionEnum::SETTINGS_UPDATE]);

        $this->put(route('tool.file-settings.store'), [
            'external_file_upload_enabled' => true,
            'data' => ['name' => 'project', 'fileTypes' => [['name' => 'pdf']], 'fileSize' => 10],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertFalse($this->uploadEnabled());
        $this->assertObjectNotHasProperty('external_file_upload_enabled', app(GeneralSettings::class));

        $props = $this->get(route('tool.file-settings.index'))
            ->assertOk()
            ->getOriginalContent()
            ->getData()['page']['props'];
        $this->assertArrayNotHasKey('externalFileUploadEnabled', $props);
    }

    #[Test]
    public function external_access_settings_page_exposes_the_flag(): void
    {
        $this->actingAsAdmin();
        $this->setUploadEnabled(true);

        $props = $this->get(route('settings.external-access.index'))
            ->assertOk()
            ->getOriginalContent()
            ->getData()['page']['props'];

        $this->assertTrue($props['settings']['file_upload_enabled']);
    }
}
