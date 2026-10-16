<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Dateiupload-Komponente: Upload-Bereiche (Platzierungen) mit eigenen Grenzen, "Sichtbar in" je Datei.
 */
final class ProjectDocumentUploadAreasTest extends FeatureTestCase
{
    private Project $project;

    private ProjectTab $tabA;

    private ProjectTab $tabB;

    private ComponentInTab $areaA;

    private ComponentInTab $areaB;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(GeneralSettings::class);
        $settings->allowed_project_file_mimetypes = ['*'];
        $settings->allowed_project_file_size = 50;
        $settings->save();

        $this->project = Project::factory()->create();
        $this->tabA = ProjectTab::factory()->create(['name' => 'Technik', 'visible_for_all' => true]);
        $this->tabB = ProjectTab::factory()->create(['name' => 'Presse', 'visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Project Documents',
            'type' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
            'data' => [],
            'special' => true,
            'permission_type' => 'allSeeAndEdit',
        ]);
        $this->areaA = ComponentInTab::create([
            'project_tab_id' => $this->tabA->id,
            'component_id' => $component->id,
            'order' => 0,
            'scope' => [$this->tabA->id],
        ]);
        $this->areaB = ComponentInTab::create([
            'project_tab_id' => $this->tabB->id,
            'component_id' => $component->id,
            'order' => 0,
            'scope' => [$this->tabB->id],
        ]);
    }

    #[Test]
    public function default_upload_stays_in_the_areas_showing_its_tab(): void
    {
        $this->actingAsAdmin();

        $this->upload('rider.pdf')->assertOk();

        $file = ProjectFile::query()->firstOrFail();
        $this->assertNull($file->visible_in);
        $this->assertSame('tab:' . $this->areaA->id, $file->uploaded_via);
        $this->documents($this->areaA)->assertJsonCount(1, 'documents')
            ->assertJsonPath('documents.0.visible_keys', ['tab:' . $this->areaA->id]);
        $this->documents($this->areaB)->assertJsonCount(0, 'documents');
    }

    #[Test]
    public function upload_can_be_shown_in_further_areas(): void
    {
        $this->actingAsAdmin();

        $this->upload('rider.pdf', ['visibleIn' => ['tab:' . $this->areaA->id, 'tab:' . $this->areaB->id]])->assertOk();

        $this->documents($this->areaB)->assertJsonCount(1, 'documents');
        $this->documents($this->areaA)->assertJsonCount(1, 'documents');
        // weitere Bereiche aus den Grunddaten der Test-DB können dazukommen
        $labels = array_column($this->documents($this->areaA)->json('placements'), 'label');
        $this->assertContains('Technik', $labels);
        $this->assertContains('Presse', $labels);
    }

    #[Test]
    public function visibility_can_be_changed_after_upload(): void
    {
        $this->actingAsAdmin();
        $this->upload('rider.pdf')->assertOk();
        $file = ProjectFile::query()->firstOrFail();

        $this->patchJson(route('project_files.visibility', $file), ['visible_in' => ['tab:' . $this->areaB->id]])
            ->assertOk();
        $this->documents($this->areaA)->assertJsonCount(0, 'documents');
        $this->documents($this->areaB)->assertJsonCount(1, 'documents');

        // zurück auf den Standard → wieder dynamisch über die Tab-Auswahl
        $this->patchJson(route('project_files.visibility', $file), ['visible_in' => ['tab:' . $this->areaA->id]])
            ->assertOk();
        $this->assertNull($file->fresh()->visible_in);

        $this->patchJson(route('project_files.visibility', $file), ['visible_in' => ['tab:999999']])
            ->assertUnprocessable();
    }

    #[Test]
    public function area_limits_are_checked_on_upload(): void
    {
        $this->actingAsAdmin();
        $this->patch(route('tab.update.component.upload-settings', $this->areaA), [
            'allowed_extensions' => ['pdf', 'exe-not-allowed'],
            'max_file_size_mb' => 1,
            'max_files' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(
            ['allowed_extensions' => ['pdf'], 'max_file_size_mb' => 1, 'max_files' => 1],
            $this->areaA->fresh()->upload_settings
        );

        $this->upload('notes.docx', [], 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertUnprocessable();
        $this->upload('big.pdf', [], 2048)->assertUnprocessable();
        $this->upload('rider.pdf')->assertOk();
        $this->upload('second.pdf')->assertUnprocessable();

        $this->assertSame(1, ProjectFile::query()->count());
    }

    #[Test]
    public function shared_file_is_downloadable_via_the_tab_of_the_target_area(): void
    {
        $this->actingAsAdmin();
        $this->upload('rider.pdf', ['visibleIn' => ['tab:' . $this->areaB->id]])->assertOk();
        $file = ProjectFile::query()->firstOrFail();

        // Upload-Tab wird eingeschränkt: Sicht nur noch über den Bereich in "Presse"
        $this->tabA->update(['visible_for_all' => false]);
        $user = $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $this->project->users()->attach($user->id);

        $this->get(route('download_file', $file))->assertOk();
        $this->documents($this->areaB)->assertJsonCount(1, 'documents');
        $this->assertCount(
            1,
            app(ProjectTabDocumentService::class)->loadVisibleDocuments($this->project, $user)
        );
        $this->assertSame(
            ['tab:' . $this->areaB->id],
            app(ProjectTabDocumentService::class)->buildAllDocumentsPayload($this->project, $user)['documents'][0]->visible_keys
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function upload(
        string $name,
        array $extra = [],
        int $kilobytes = 10,
        string $mime = 'application/pdf',
    ): TestResponse {
        return $this->postJson(route('project_files.store', $this->project), [
            'file' => UploadedFile::fake()->create($name, $kilobytes, $mime),
            'tabId' => $this->tabA->id,
            'placementKey' => 'tab:' . $this->areaA->id,
            ...$extra,
        ]);
    }

    private function documents(ComponentInTab $area): TestResponse
    {
        return $this->getJson(route('projects.tabs.documents', [
            'project' => $this->project->id,
            'componentInTab' => $area->id,
        ]))->assertOk();
    }
}
