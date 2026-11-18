<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AdminRoleRevocationTest extends FeatureTestCase
{
    #[Test]
    public function revoking_own_admin_role_redirects_to_own_info_page_instead_of_forbidden_permissions_tab(): void
    {
        $admin = $this->actingAsAdmin();
        User::factory()->create()->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->from(route('user.edit.permissions', $admin))
            ->patch(route('user.update.permissions-and-roles', $admin), [
                'permissions' => [],
                'roles' => [],
                'source' => 'admin',
            ])
            ->assertRedirect(route('user.edit.info', $admin))
            ->assertSessionHas('success');

        $this->assertFalse($admin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }

    #[Test]
    public function revoking_admin_role_of_another_person_redirects_back(): void
    {
        $admin = $this->actingAsAdmin();
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->from(route('user.edit.permissions', $otherAdmin))
            ->patch(route('user.update.permissions-and-roles', $otherAdmin), [
                'permissions' => [],
                'roles' => [],
                'source' => 'admin',
            ])
            ->assertRedirect(route('user.edit.permissions', $otherAdmin));

        $this->assertFalse($otherAdmin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
        $this->assertTrue($admin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }

    #[Test]
    public function saving_own_permissions_while_keeping_admin_role_redirects_back(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('user.edit.permissions', $admin))
            ->patch(route('user.update.permissions-and-roles', $admin), [
                'permissions' => [],
                'roles' => [RoleEnum::ARTWORK_ADMIN->value],
            ])
            ->assertRedirect(route('user.edit.permissions', $admin));
    }

    #[Test]
    public function last_admin_still_cannot_revoke_own_admin_role(): void
    {
        $admin = $this->actingAsAdmin();
        User::role(RoleEnum::ARTWORK_ADMIN->value)
            ->whereKeyNot($admin->getKey())
            ->get()
            ->each(fn (User $user) => $user->removeRole(RoleEnum::ARTWORK_ADMIN->value));

        $this->from(route('user.edit.permissions', $admin))
            ->patch(route('user.update.permissions-and-roles', $admin), [
                'permissions' => [],
                'roles' => [],
            ])
            ->assertRedirect(route('user.edit.permissions', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }
}
