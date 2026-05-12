<?php

namespace Tests\Unit\Policies;

use App\Policies\InventoryTagGroupPolicy;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InventoryTagGroupPolicyTest extends TestCase
{
    private InventoryTagGroupPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(InventoryTagGroupPolicy::class);
    }

    #[Test]
    public function admin_cannot_view_any_due_to_locked_policy(): void
    {
        $this->assertFalse($this->policy->viewAny($this->adminUser()));
    }

    #[Test]
    public function user_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny(User::factory()->create()));
    }

    #[Test]
    public function user_cannot_view(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new InventoryTagGroup()));
    }

    #[Test]
    public function user_cannot_create(): void
    {
        $this->assertFalse($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_cannot_update(): void
    {
        $this->assertFalse($this->policy->update(User::factory()->create(), new InventoryTagGroup()));
    }

    #[Test]
    public function user_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete(User::factory()->create(), new InventoryTagGroup()));
    }

    #[Test]
    public function user_cannot_restore(): void
    {
        $this->assertFalse($this->policy->restore(User::factory()->create(), new InventoryTagGroup()));
    }

    #[Test]
    public function user_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete(User::factory()->create(), new InventoryTagGroup()));
    }
}
