<?php

namespace Tests\Unit\Policies;

use App\Policies\AccommodationRoomTypePolicy;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AccommodationRoomTypePolicyTest extends TestCase
{
    private AccommodationRoomTypePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(AccommodationRoomTypePolicy::class);
    }

    #[Test]
    public function admin_cannot_view_any_due_to_locked_policy(): void
    {
        $admin = $this->adminUser();

        $this->assertFalse($this->policy->viewAny($admin));
    }

    #[Test]
    public function user_cannot_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function user_cannot_view_model(): void
    {
        $user = User::factory()->create();
        $model = new AccommodationRoomType();

        $this->assertFalse($this->policy->view($user, $model));
    }

    #[Test]
    public function user_cannot_create(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->create($user));
    }

    #[Test]
    public function user_cannot_update(): void
    {
        $user = User::factory()->create();
        $model = new AccommodationRoomType();

        $this->assertFalse($this->policy->update($user, $model));
    }

    #[Test]
    public function user_cannot_delete(): void
    {
        $user = User::factory()->create();
        $model = new AccommodationRoomType();

        $this->assertFalse($this->policy->delete($user, $model));
    }

    #[Test]
    public function user_cannot_restore(): void
    {
        $user = User::factory()->create();
        $model = new AccommodationRoomType();

        $this->assertFalse($this->policy->restore($user, $model));
    }

    #[Test]
    public function user_cannot_force_delete(): void
    {
        $user = User::factory()->create();
        $model = new AccommodationRoomType();

        $this->assertFalse($this->policy->forceDelete($user, $model));
    }
}
