<?php

namespace Tests\Feature;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 5 (M4, M5, N9): Schreibrecht im Projekt reicht nicht mehr, um sich oder
 * anderen Budget- und Löschrecht zu geben oder Personen ohne „Projekte verwalten“ zur Projektleitung
 * zu machen. Die eigene Teamzugehörigkeit ändert nur eine andere Person. Die letzte Person mit
 * Admin-Rolle kann die Rolle nicht verlieren (löschen dürfen ohnehin nur andere Admins).
 */
final class SecurityAuditProjectRightsRegressionTest extends FeatureTestCase
{
    private function projectWithWriteMember(User $member): Project
    {
        $project = Project::factory()->create();
        $project->users()->attach($member->id, ['can_write' => true]);

        return $project;
    }

    /**
     * @param array<string, bool> $rights
     */
    private function updateTeam(Project $project, User $member, array $rights): void
    {
        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [$member->id => array_merge(['can_write' => true], $rights)],
            'assigned_departments' => [],
        ])->assertRedirect();
    }

    private function pivot(Project $project, User $member): object
    {
        return $project->users()->whereKey($member->id)->firstOrFail()->pivot;
    }

    #[Test]
    public function a_write_member_cannot_grant_budget_or_delete_rights(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);
        $project = $this->projectWithWriteMember($member);

        $this->updateTeam($project, $member, ['access_budget' => true, 'delete_permission' => true]);

        $pivot = $this->pivot($project, $member);
        $this->assertFalse((bool) $pivot->access_budget);
        $this->assertFalse((bool) $pivot->delete_permission);
        $this->assertTrue((bool) $pivot->can_write);
    }

    #[Test]
    public function a_write_member_cannot_remove_existing_budget_rights_either(): void
    {
        $member = User::factory()->create();
        $colleague = User::factory()->create();
        $this->actingAs($member);
        $project = $this->projectWithWriteMember($member);
        $project->users()->attach($colleague->id, ['access_budget' => true]);

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [
                $member->id => ['can_write' => true],
                $colleague->id => ['access_budget' => false],
            ],
            'assigned_departments' => [],
        ])->assertRedirect();

        $this->assertTrue((bool) $this->pivot($project, $colleague)->access_budget);
    }

    #[Test]
    public function the_project_lead_can_grant_budget_and_delete_rights(): void
    {
        $lead = User::factory()->create();
        $member = User::factory()->create();
        $this->actingAs($lead);
        $project = Project::factory()->create();
        $project->users()->attach($lead->id, ['is_manager' => true, 'can_write' => true]);
        $project->users()->attach($member->id);

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [
                $lead->id => ['is_manager' => true, 'can_write' => true],
                $member->id => ['access_budget' => true, 'delete_permission' => true],
            ],
            'assigned_departments' => [],
        ])->assertRedirect();

        $pivot = $this->pivot($project, $member);
        $this->assertTrue((bool) $pivot->access_budget);
        $this->assertTrue((bool) $pivot->delete_permission);
    }

    #[Test]
    public function global_rights_allow_granting_only_the_matching_project_right(): void
    {
        $member = $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);
        $project = $this->projectWithWriteMember($member);

        $this->updateTeam($project, $member, ['access_budget' => true, 'delete_permission' => true]);

        $pivot = $this->pivot($project, $member);
        $this->assertTrue((bool) $pivot->access_budget);
        $this->assertFalse((bool) $pivot->delete_permission);
    }

    #[Test]
    public function only_people_with_project_management_permission_become_project_lead(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $withoutPermission = User::factory()->create();
        $withPermission = User::factory()->create();
        $withPermission->givePermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value);

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [
                $withoutPermission->id => ['is_manager' => true],
                $withPermission->id => ['is_manager' => true],
            ],
            'assigned_departments' => [],
        ])->assertRedirect();

        $this->assertFalse((bool) $this->pivot($project, $withoutPermission)->is_manager);
        $this->assertTrue((bool) $this->pivot($project, $withPermission)->is_manager);
    }

    #[Test]
    public function the_app_cannot_grant_budget_rights_to_a_write_member_either(): void
    {
        $member = User::factory()->create();
        $project = $this->projectWithWriteMember($member);
        Passport::actingAs($member, ['app']);

        $this->patchJson(route('app.v1.projects.team.update', [$project, $member]), [
            'access_budget' => true,
        ])->assertOk();

        $this->assertFalse((bool) $this->pivot($project, $member)->access_budget);
    }

    #[Test]
    public function own_team_membership_cannot_be_changed_in_the_own_profile(): void
    {
        // Fremde Profile bearbeiten setzt „Mitarbeitende verwalten“ voraus; das Recht erlaubt auch Teams.
        $actor = $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $team = Department::factory()->create();
        $other = User::factory()->create();

        $this->patch(route('user.update', $actor), [
            'first_name' => $actor->first_name,
            'last_name' => $actor->last_name,
            'departments' => [['id' => $team->id]],
        ])->assertSessionHas('error');

        $this->assertFalse($actor->departments()->whereKey($team->id)->exists());

        $this->patch(route('user.update', $other), [
            'first_name' => $other->first_name,
            'last_name' => $other->last_name,
            'departments' => [['id' => $team->id]],
        ])->assertSessionMissing('error');

        $this->assertTrue($other->departments()->whereKey($team->id)->exists());
    }

    #[Test]
    public function own_team_membership_cannot_be_changed_on_the_team_page(): void
    {
        $actor = $this->actingAsUserWith(PermissionEnum::TEAM_UPDATE->value);
        $team = Department::factory()->create();
        $other = User::factory()->create();

        $this->patch(route('departments.edit', $team), [
            'name' => $team->name,
            'svg_name' => $team->svg_name,
            'users' => [['id' => $actor->id], ['id' => $other->id]],
        ])->assertSessionHas('error');

        $this->assertSame(0, $team->users()->count());

        $this->patch(route('departments.edit', $team), [
            'name' => $team->name,
            'svg_name' => $team->svg_name,
            'users' => [['id' => $other->id]],
        ])->assertSessionMissing('error');

        $this->assertTrue($team->users()->whereKey($other->id)->exists());
    }

    #[Test]
    public function admins_may_still_change_their_own_teams(): void
    {
        $admin = $this->actingAsAdmin();
        $team = Department::factory()->create();

        $this->patch(route('user.update', $admin), [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'departments' => [['id' => $team->id]],
        ])->assertSessionMissing('error');

        $this->assertTrue($admin->departments()->whereKey($team->id)->exists());
    }

    private function onlyAdmin(): User
    {
        User::role(RoleEnum::ARTWORK_ADMIN->value)->get()->each->removeRole(RoleEnum::ARTWORK_ADMIN->value);

        return $this->actingAsAdmin();
    }

    #[Test]
    public function the_last_admin_keeps_the_admin_role(): void
    {
        $admin = $this->onlyAdmin();

        $this->patch(route('user.update.permissions-and-roles', $admin), [
            'permissions' => [],
            'roles' => [],
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }

    #[Test]
    public function the_admin_role_can_be_removed_while_another_admin_exists(): void
    {
        $this->onlyAdmin();
        $secondAdmin = User::factory()->create();
        $secondAdmin->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->patch(route('user.update.permissions-and-roles', $secondAdmin), [
            'permissions' => [],
            'roles' => [],
        ])->assertSessionMissing('error');

        $this->assertFalse($secondAdmin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }
}
