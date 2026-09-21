<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Events\DeleteDocumentInProject;
use Artwork\Modules\Project\Events\UploadNewDocumentInProject;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ExternalDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        Event::fake([UploadNewDocumentInProject::class, DeleteDocumentInProject::class]);

        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['pdf'];
        $settings->allowed_project_file_size = 10;
        $settings->external_file_upload_enabled = true;
        $settings->save();
    }

    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab, component: Component}
     */
    private function context(bool $write = true, string $type = 'ProjectDocumentsComponent'): array
    {
        $external = ExternalAccess::factory()->active()->create();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $factory = ExternalAccessScope::factory();
        if ($write) {
            $factory = $factory->write();
        }
        $factory->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);
        $component = Component::create(['name' => 'Dokumente', 'type' => $type, 'data' => []]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 0, 'scope' => [$tab->id]]);
        $this->actingAs($external, 'external');

        return compact('external', 'project', 'tab', 'component');
    }

    private function params(array $ctx): array
    {
        return ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'component' => $ctx['component']->id];
    }

    #[Test]
    public function external_with_write_scope_can_upload_list_and_delete_own_file(): void
    {
        $ctx = $this->context();

        $upload = $this->postJson(route('external.project.tab.documents.store', $this->params($ctx)), [
            'file' => UploadedFile::fake()->create('rider.pdf', 120, 'application/pdf'),
        ]);
        $upload->assertCreated()->assertJsonPath('document.name', 'rider.pdf')->assertJsonPath('document.uploaded_by_me', true);

        $file = ProjectFile::query()->where('project_id', $ctx['project']->id)->firstOrFail();
        $this->assertSame($ctx['tab']->id, (int) $file->tab_id);
        $this->assertSame($ctx['external']->id, (int) $file->external_access_id);
        Storage::assertExists('project_files/' . $file->basename);

        $this->getJson(route('external.project.tab.documents.index', $this->params($ctx)))
            ->assertOk()
            ->assertJsonCount(1, 'documents');

        $this->get(route('external.project.tab.documents.download', ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'file' => $file->id]))
            ->assertOk();

        $this->deleteJson(route('external.project.tab.documents.destroy', ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'file' => $file->id]))
            ->assertOk();
        $this->assertSoftDeleted('project_files', ['id' => $file->id]);
    }

    #[Test]
    public function external_cannot_delete_files_uploaded_by_others(): void
    {
        $ctx = $this->context();
        $foreign = $ctx['project']->project_files()->create([
            'tab_id' => $ctx['tab']->id,
            'name' => 'intern.pdf',
            'basename' => 'abc.pdf',
        ]);

        $this->deleteJson(route('external.project.tab.documents.destroy', ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'file' => $foreign->id]))
            ->assertNotFound();
        $this->assertDatabaseHas('project_files', ['id' => $foreign->id, 'deleted_at' => null]);
    }

    #[Test]
    public function files_of_other_tabs_are_not_listed_or_downloadable(): void
    {
        $ctx = $this->context();
        $otherTab = ProjectTab::factory()->create();
        $hidden = $ctx['project']->project_files()->create([
            'tab_id' => $otherTab->id,
            'name' => 'geheim.pdf',
            'basename' => 'geheim.pdf',
        ]);

        $this->getJson(route('external.project.tab.documents.index', $this->params($ctx)))
            ->assertOk()
            ->assertJsonCount(0, 'documents');
        $this->get(route('external.project.tab.documents.download', ['project' => $ctx['project']->id, 'tab' => $ctx['tab']->id, 'file' => $hidden->id]))
            ->assertNotFound();
    }

    #[Test]
    public function read_only_scope_cannot_upload(): void
    {
        $ctx = $this->context(write: false);

        $this->postJson(route('external.project.tab.documents.store', $this->params($ctx)), [
            'file' => UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    #[Test]
    public function disallowed_file_type_is_rejected(): void
    {
        $ctx = $this->context();

        $this->postJson(route('external.project.tab.documents.store', $this->params($ctx)), [
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ])->assertStatus(422);
        $this->assertSame(0, ProjectFile::query()->where('project_id', $ctx['project']->id)->count());
    }

    #[Test]
    public function upload_to_non_document_component_is_rejected(): void
    {
        $ctx = $this->context(type: 'TextField');

        $this->postJson(route('external.project.tab.documents.store', $this->params($ctx)), [
            'file' => UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf'),
        ])->assertStatus(422);
    }
}
