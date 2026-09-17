<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Reiter "Nicht zugewiesen" in der Projekt-Komponente "Verträge & Dokumente".
 */
final class ProjectContractsDocumentsUnassignedRequestsTest extends FeatureTestCase
{
    private function tabWithContractsDocumentsComponent(): ProjectTab
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Verträge & Dokumente',
            'type' => ProjectTabComponentEnum::PROJECT_CONTRACTS_DOCUMENTS->value,
            'data' => [],
        ]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);

        return $tab;
    }

    #[Test]
    public function project_tab_lists_open_unassigned_requests_of_this_project_for_editors(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_EDIT->value);
        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($viewer->id);
        $otherProject = Project::factory()->create();
        $tab = $this->tabWithContractsDocumentsComponent();

        // sichtbar: dieses Projekt, niemandem zugewiesen, offen
        $unassigned = DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => null,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        // nicht sichtbar: anderes Projekt / bereits zugewiesen / erledigt
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => null,
            'project_id' => $otherProject->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $viewer->id,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => null,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_COMPLETED,
        ]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('projectUnassignedRequests', 1)
                ->where('projectUnassignedRequests.0.id', $unassigned->id)
                ->where('projectUnassignedRequests.0.requester.id', $requester->id));
    }

    #[Test]
    public function project_tab_hides_unassigned_requests_without_permission(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer);
        $project = Project::factory()->create();
        $project->users()->attach($viewer->id);
        $tab = $this->tabWithContractsDocumentsComponent();

        DocumentRequest::create([
            'requester_id' => User::factory()->create()->id,
            'requested_id' => null,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('projectUnassignedRequests', 0));
    }
}
