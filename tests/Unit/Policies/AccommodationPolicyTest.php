<?php

namespace Tests\Unit\Policies;

use App\Policies\AccommodationPolicy;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AccommodationPolicyTest extends TestCase
{
    private AccommodationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(AccommodationPolicy::class);
    }

    #[Test]
    public function admin_can_view_any(): void
    {
        $admin = $this->adminUser();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    #[Test]
    public function authenticated_user_can_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function authenticated_user_can_view_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->view($user, $accommodation));
    }

    #[Test]
    public function authenticated_user_cannot_view_non_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = new Accommodation();

        $this->assertFalse($this->policy->view($user, $accommodation));
    }

    #[Test]
    public function authenticated_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function authenticated_user_can_update_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->update($user, $accommodation));
    }

    #[Test]
    public function authenticated_user_can_delete_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->delete($user, $accommodation));
    }

    #[Test]
    public function authenticated_user_can_restore_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->restore($user, $accommodation));
    }

    #[Test]
    public function authenticated_user_can_force_delete_existing_accommodation(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->forceDelete($user, $accommodation));
    }
}
