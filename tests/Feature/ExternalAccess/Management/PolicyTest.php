<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PolicyTest extends TestCase
{
    #[Test]
    public function crm_view_user_can_view_but_not_manage(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create();

        $this->assertTrue($user->can('view', $access));
        $this->assertFalse($user->can('manage', $access));
        $this->assertFalse($user->can('relink', $access));
    }

    #[Test]
    public function crm_manager_can_view_manage_and_relink(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::CRM_VIEW, PermissionEnum::CRM_MANAGER]);
        $access = ExternalAccess::factory()->create();

        $this->assertTrue($user->can('view', $access));
        $this->assertTrue($user->can('manage', $access));
        $this->assertTrue($user->can('relink', $access));
    }

    #[Test]
    public function inviter_can_manage_but_not_relink(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);

        $this->assertTrue($inviter->can('manage', $access));
        $this->assertFalse($inviter->can('relink', $access));
    }

    #[Test]
    public function non_inviter_without_manager_cannot_manage(): void
    {
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW]); // seeds roles/permissions
        $stranger = User::factory()->create();
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => User::factory()->create()->id]);

        $this->assertFalse($stranger->can('manage', $access));
    }

    #[Test]
    public function artwork_admin_can_manage_and_relink_via_gate_before(): void
    {
        $admin = $this->adminUser();
        $access = ExternalAccess::factory()->create();

        $this->assertTrue($admin->can('manage', $access));
        $this->assertTrue($admin->can('relink', $access));
    }
}
