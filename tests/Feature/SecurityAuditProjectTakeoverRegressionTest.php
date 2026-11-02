<?php

namespace Tests\Feature;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 26.10.2026, C-1 (H-1 + H-3): Checklisten verschaffen keinen Zugang mehr zu fremden Projekten,
 * und Schreibrecht im Projekt reicht nicht, um sich selbst zur Projektleitung zu machen oder die
 * bestehende Leitung zu entfernen.
 */
final class SecurityAuditProjectTakeoverRegressionTest extends FeatureTestCase
{
    private function pivot(Project $project, User $user): ?object
    {
        return $project->users()->whereKey($user->id)->first()?->pivot;
    }

    private function projectWithManager(): array
    {
        $project = Project::factory()->create();
        $manager = User::factory()->create();
        $manager->givePermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value);
        $project->users()->attach($manager->id, ['is_manager' => true, 'can_write' => true]);

        return [$project, $manager];
    }

    #[Test]
    public function own_project_permission_does_not_allow_checklists_in_foreign_projects(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $project = Project::factory()->create();

        $this->post(route('checklists.store'), [
            'name' => 'x',
            'project_id' => $project->id,
            'private' => false,
        ])->assertForbidden();

        $this->assertNull($this->pivot($project, $user));
        $this->assertDatabaseMissing('checklists', ['project_id' => $project->id]);
    }

    #[Test]
    public function event_creator_cannot_create_checklists_in_the_project(): void
    {
        $user = $this->actingAsUserWith([]);
        $project = Project::factory()->create();
        Event::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $this->post(route('checklists.store'), [
            'name' => 'x',
            'project_id' => $project->id,
            'private' => false,
        ])->assertForbidden();

        $this->assertNull($this->pivot($project, $user));
    }

    #[Test]
    public function read_only_member_creates_a_checklist_without_gaining_write_access(): void
    {
        $user = $this->actingAsUserWith([]);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false]);

        $this->post(route('checklists.store'), [
            'name' => 'Read-only list',
            'project_id' => $project->id,
            'private' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('checklists', ['name' => 'Read-only list', 'project_id' => $project->id]);
        $this->assertFalse((bool) $this->pivot($project, $user)->can_write);
    }

    #[Test]
    public function checklist_cannot_be_moved_into_a_foreign_project(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $checklist = Checklist::factory()->create(['user_id' => $user->id, 'project_id' => null]);
        $project = Project::factory()->create();

        $this->patch(route('checklists.update', $checklist), ['project_id' => $project->id])
            ->assertForbidden();

        $this->assertNull($checklist->fresh()->project_id);
        $this->assertNull($this->pivot($project, $user));
    }

    #[Test]
    public function writer_cannot_make_themselves_manager_or_evict_the_managers(): void
    {
        [$project, $manager] = $this->projectWithManager();
        $writer = $this->actingAsUserWith([]);
        $project->users()->attach($writer->id, ['can_write' => true]);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'assignedUsers' => [$writer->id],
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $this->pivot($project, $writer)->is_manager);
        $this->assertTrue((bool) $this->pivot($project, $manager)->is_manager);
    }

    #[Test]
    public function project_update_without_manager_list_keeps_the_managers(): void
    {
        [$project, $manager] = $this->projectWithManager();
        $this->actingAs($manager);

        $this->patch(route('projects.update', $project), ['name' => 'Renamed'])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $this->pivot($project, $manager)->is_manager);
    }

    #[Test]
    public function manager_can_only_appoint_users_with_project_management(): void
    {
        [$project, $manager] = $this->projectWithManager();
        $withoutPermission = User::factory()->create();
        $withPermission = User::factory()->create();
        $withPermission->givePermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value);
        $this->actingAs($manager);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'assignedUsers' => [$manager->id, $withoutPermission->id, $withPermission->id],
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->pivot($project, $withoutPermission));
        $this->assertTrue((bool) $this->pivot($project, $withPermission)->is_manager);
    }

    #[Test]
    public function removed_manager_stays_in_the_team(): void
    {
        [$project, $manager] = $this->projectWithManager();
        $otherManager = User::factory()->create();
        $project->users()->attach($otherManager->id, ['is_manager' => true, 'can_write' => true]);
        $this->actingAs($manager);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'assignedUsers' => [$manager->id],
        ])->assertSessionHasNoErrors();

        $pivot = $this->pivot($project, $otherManager);
        $this->assertNotNull($pivot);
        $this->assertFalse((bool) $pivot->is_manager);
    }

    #[Test]
    public function basis_user_cannot_take_over_a_foreign_project(): void
    {
        [$project, $manager] = $this->projectWithManager();
        $attacker = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);

        $this->post(route('checklists.store'), ['name' => 'x', 'project_id' => $project->id, 'private' => false]);
        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'assignedUsers' => [$attacker->id],
        ]);
        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [$attacker->id => [
                'can_write' => true,
                'access_budget' => true,
                'delete_permission' => true,
                'is_manager' => true,
            ]],
            'assigned_departments' => [],
        ]);

        $this->assertNull($this->pivot($project, $attacker));
        $this->assertTrue((bool) $this->pivot($project, $manager)->is_manager);
    }
}
