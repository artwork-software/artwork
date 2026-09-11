<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Shift\Services\ShiftConfirmationEligibilityService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\FeatureTestCase;

/**
 * Teilnahme am Zu-/Absage-Flow = User mit dem Recht „Darf Schichten annehmen/ablehnen".
 */
final class ShiftConfirmationEligibilityServiceTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate(PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function service(): ShiftConfirmationEligibilityService
    {
        // scoped Binding: frische Instanz je Test, damit die Memoisierung nicht klebt
        return new ShiftConfirmationEligibilityService();
    }

    #[Test]
    public function user_with_direct_permission_is_eligible(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value);

        $this->assertTrue($this->service()->isEligible($user));
        $this->assertTrue($this->service()->isEligibleUserId($user->id));
    }

    #[Test]
    public function user_with_permission_via_role_is_eligible(): void
    {
        $role = Role::findOrCreate('Rolle mit Schichtantwort', 'web');
        $role->givePermissionTo(PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($this->service()->isEligible($user));
    }

    #[Test]
    public function user_without_permission_and_externals_are_not_eligible(): void
    {
        $user = User::factory()->create();
        $freelancer = Freelancer::factory()->create();

        $service = $this->service();
        $this->assertFalse($service->isEligible($user));
        $this->assertFalse($service->isEligible($freelancer));
        $this->assertFalse($service->isEligible(null));
    }

    #[Test]
    public function admin_role_alone_does_not_make_a_user_eligible(): void
    {
        $admin = $this->actingAsAdmin();

        $this->assertFalse($this->service()->isEligible($admin));
    }

    #[Test]
    public function missing_permission_row_means_nobody_is_eligible(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value);

        Permission::query()->where('name', PermissionEnum::CAN_RESPOND_TO_SHIFT_ASSIGNMENTS->value)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($this->service()->isEligible($user));
    }
}
