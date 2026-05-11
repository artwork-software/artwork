<?php

namespace Tests\Unit\Policies;

use App\Policies\DisclosureComponentsPolicy;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DisclosureComponentsPolicyTest extends TestCase
{
    private DisclosureComponentsPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(DisclosureComponentsPolicy::class);
    }

    #[Test]
    public function admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser()));
    }

    #[Test]
    public function user_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny(User::factory()->create()));
    }

    #[Test]
    public function user_cannot_view_unsaved(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new DisclosureComponents()));
    }

    #[Test]
    public function user_can_create(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_cannot_update_unsaved(): void
    {
        $this->assertFalse($this->policy->update(User::factory()->create(), new DisclosureComponents()));
    }

    #[Test]
    public function user_cannot_delete_unsaved(): void
    {
        $this->assertFalse($this->policy->delete(User::factory()->create(), new DisclosureComponents()));
    }

    #[Test]
    public function user_cannot_restore_unsaved(): void
    {
        $this->assertFalse($this->policy->restore(User::factory()->create(), new DisclosureComponents()));
    }

    #[Test]
    public function user_cannot_force_delete_unsaved(): void
    {
        $this->assertFalse($this->policy->forceDelete(User::factory()->create(), new DisclosureComponents()));
    }
}
