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
 * Reiter "An andere zugewiesen" in der Projekt-Komponente "Verträge & Dokumente".
 */
final class ProjectContractsDocumentsAssignedToOthersRequestsTest extends FeatureTestCase
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
    public function project_tab_lists_open_requests_of_this_project_assigned_to_others_for_editors(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::DOCUMENT_REQUEST_EDIT->value);
        $requester = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($viewer->id);
        $otherProject = Project::factory()->create();
        $tab = $this->tabWithContractsDocumentsComponent();

        // sichtbar: dieses Projekt, an andere Person zugewiesen, offen – egal von wem erstellt
        $foreignOpen = DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_OPEN,
            'deadline_date' => '2026-10-01',
        ]);
        $ownCreatedForOther = DocumentRequest::create([
            'requester_id' => $viewer->id,
            'requested_id' => $other->id,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_IN_PROGRESS,
            'deadline_date' => '2026-11-01',
        ]);

        // nicht sichtbar: anderes Projekt / mir zugewiesen / niemandem zugewiesen / erledigt
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
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
            'status' => DocumentRequest::STATUS_OPEN,
        ]);
        DocumentRequest::create([
            'requester_id' => $requester->id,
            'requested_id' => $other->id,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_COMPLETED,
        ]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('projectAssignedToOthersRequests', 2)
                ->where('projectAssignedToOthersRequests.0.id', $foreignOpen->id)
                ->where('projectAssignedToOthersRequests.0.requester.id', $requester->id)
                ->where('projectAssignedToOthersRequests.0.requested.id', $other->id)
                ->where('projectAssignedToOthersRequests.1.id', $ownCreatedForOther->id));
    }

    #[Test]
    public function project_tab_hides_requests_assigned_to_others_without_permission(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer);
        $project = Project::factory()->create();
        $project->users()->attach($viewer->id);
        $tab = $this->tabWithContractsDocumentsComponent();

        DocumentRequest::create([
            'requester_id' => User::factory()->create()->id,
            'requested_id' => User::factory()->create()->id,
            'project_id' => $project->id,
            'status' => DocumentRequest::STATUS_OPEN,
        ]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('projectAssignedToOthersRequests', 0));
    }
}
