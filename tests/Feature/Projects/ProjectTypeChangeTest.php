<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Budget\Services\BudgetCacheService;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

final class ProjectTypeChangeTest extends FeatureTestCase
{
    #[Test]
    public function a_project_can_be_converted_into_a_project_group(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['is_group' => false]);

        $this->patch(route('projects.update.type', $project), ['is_group' => true])
            ->assertRedirect();

        $this->assertTrue($project->fresh()->is_group);
        $this->assertSame(['Project converted to project group'], $this->historyTranslationKeys($project));
    }

    #[Test]
    public function converting_a_project_into_a_group_removes_its_own_group_membership(): void
    {
        $this->actingAsAdmin();
        $existingGroup = Project::factory()->create(['is_group' => true]);
        $project = Project::factory()->create(['is_group' => false]);
        $existingGroup->projectsOfGroup()->attach($project->id);

        $this->patch(route('projects.update.type', $project), ['is_group' => true])
            ->assertRedirect();

        $this->assertDatabaseMissing('project_groups', [
            'group_id' => $existingGroup->id,
            'project_id' => $project->id,
        ]);
        $this->assertTrue($existingGroup->fresh()->is_group);
    }

    #[Test]
    public function converting_a_group_into_a_project_detaches_but_keeps_the_subprojects(): void
    {
        $this->actingAsAdmin();
        $group = Project::factory()->create(['is_group' => true]);
        $subProjects = Project::factory()->count(2)->create(['is_group' => false]);
        $group->projectsOfGroup()->attach($subProjects->pluck('id'));

        $this->patch(route('projects.update.type', $group), ['is_group' => false])
            ->assertRedirect();

        $this->assertFalse($group->fresh()->is_group);
        $this->assertDatabaseMissing('project_groups', ['group_id' => $group->id]);
        foreach ($subProjects as $subProject) {
            $this->assertNotSoftDeleted('projects', ['id' => $subProject->id]);
        }
        $this->assertSame(['Project group converted to project'], $this->historyTranslationKeys($group));
    }

    #[Test]
    public function converting_drops_the_cached_budget_payloads_of_all_involved_projects(): void
    {
        // array-Store: der file-Cache der DDEV-Umgebung ist mit der laufenden App geteilt
        config(['cache.default' => 'array']);
        $budgetCache = app(BudgetCacheService::class);
        $this->actingAsAdmin();

        $group = Project::factory()->create(['is_group' => true]);
        $subProjects = Project::factory()->count(2)->create(['is_group' => false]);
        $group->projectsOfGroup()->attach($subProjects->pluck('id'));
        foreach ([$group, ...$subProjects] as $project) {
            $budgetCache->setBudgetPayload($project->id, ['cached' => true]);
        }

        $this->patch(route('projects.update.type', $group), ['is_group' => false])->assertRedirect();

        foreach ([$group, ...$subProjects] as $project) {
            $this->assertNull($budgetCache->getBudgetPayload($project->id));
        }

        // Projekt → Gruppe: die bisherige Gruppe verliert die Summen des Projekts
        $otherGroup = Project::factory()->create(['is_group' => true]);
        $member = $subProjects->first();
        $otherGroup->projectsOfGroup()->attach($member->id);
        $budgetCache->setBudgetPayload($otherGroup->id, ['cached' => true]);
        $budgetCache->setBudgetPayload($member->id, ['cached' => true]);

        $this->patch(route('projects.update.type', $member), ['is_group' => true])->assertRedirect();

        $this->assertNull($budgetCache->getBudgetPayload($otherGroup->id));
        $this->assertNull($budgetCache->getBudgetPayload($member->id));
    }

    #[Test]
    public function requesting_the_current_type_changes_nothing(): void
    {
        $this->actingAsAdmin();
        $group = Project::factory()->create(['is_group' => true]);
        $subProject = Project::factory()->create(['is_group' => false]);
        $group->projectsOfGroup()->attach($subProject->id);

        $this->patch(route('projects.update.type', $group), ['is_group' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('project_groups', ['group_id' => $group->id, 'project_id' => $subProject->id]);
        $this->assertSame([], $this->historyTranslationKeys($group));
    }

    #[Test]
    public function the_target_type_is_required(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['is_group' => false]);

        $this->patch(route('projects.update.type', $project), [])
            ->assertSessionHasErrors('is_group');

        $this->assertFalse($project->fresh()->is_group);
    }

    #[Test]
    public function users_without_write_access_cannot_change_the_type(): void
    {
        $this->actingAsUserWith([]);
        $group = Project::factory()->create(['is_group' => true]);
        $subProject = Project::factory()->create(['is_group' => false]);
        $group->projectsOfGroup()->attach($subProject->id);

        $this->patch(route('projects.update.type', $group), ['is_group' => false])
            ->assertForbidden();

        $this->assertTrue($group->fresh()->is_group);
        $this->assertDatabaseHas('project_groups', ['group_id' => $group->id, 'project_id' => $subProject->id]);
    }

    /**
     * @return array<int, string>
     */
    private function historyTranslationKeys(Project $project): array
    {
        return Activity::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->where('log_name', 'public_changes')
            ->pluck('description')
            ->all();
    }
}
