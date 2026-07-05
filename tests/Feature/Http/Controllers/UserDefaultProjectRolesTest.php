<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserDefaultProjectRolesTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_default_project_roles(): void
    {
        $user = User::factory()->create();

        $this->patch(route('user.update.defaultProjectRoles', $user), ['defaultProjectRoleIds' => []])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function user_without_permission_cannot_update_default_project_roles(): void
    {
        $this->actingAs(User::factory()->create());
        $user = User::factory()->create();

        $this->patch(route('user.update.defaultProjectRoles', $user), ['defaultProjectRoleIds' => []])
            ->assertForbidden();
    }

    #[Test]
    public function worker_manager_can_sync_default_project_roles(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();
        $roles = ProjectRole::factory()->count(2)->create();

        $response = $this->patch(route('user.update.defaultProjectRoles', $user), [
            'defaultProjectRoleIds' => $roles->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        foreach ($roles as $role) {
            $this->assertDatabaseHas('default_project_role_user', [
                'user_id' => $user->id,
                'project_role_id' => $role->id,
            ]);
        }
    }

    #[Test]
    public function non_array_payload_is_rejected_with_validation_error(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();

        $this->patchJson(route('user.update.defaultProjectRoles', $user), ['defaultProjectRoleIds' => 'not-an-array'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('defaultProjectRoleIds');
    }

    #[Test]
    public function syncing_replaces_previous_defaults_and_ignores_unknown_role_ids(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();
        $oldRole = ProjectRole::factory()->create();
        $newRole = ProjectRole::factory()->create();
        $user->defaultProjectRoles()->attach($oldRole);

        $response = $this->patch(route('user.update.defaultProjectRoles', $user), [
            'defaultProjectRoleIds' => [$newRole->id, 999999],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('default_project_role_user', [
            'user_id' => $user->id,
            'project_role_id' => $newRole->id,
        ]);
        $this->assertDatabaseMissing('default_project_role_user', [
            'user_id' => $user->id,
            'project_role_id' => $oldRole->id,
        ]);
        $this->assertDatabaseMissing('default_project_role_user', [
            'user_id' => $user->id,
            'project_role_id' => 999999,
        ]);
    }
}
