<?php

namespace Tests\Unit\Policies;

use App\Policies\InternalIssuePolicy;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InternalIssuePolicyTest extends TestCase
{
    private InternalIssuePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(InternalIssuePolicy::class);
    }

    private function existingModel(): InternalIssue
    {
        $model = new InternalIssue();
        $model->exists = true;

        return $model;
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
    public function user_can_view_existing(): void
    {
        $this->assertTrue($this->policy->view(User::factory()->create(), $this->existingModel()));
    }

    #[Test]
    public function user_cannot_view_unsaved(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new InternalIssue()));
    }

    #[Test]
    public function user_can_create(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_can_update_existing(): void
    {
        $this->assertTrue($this->policy->update(User::factory()->create(), $this->existingModel()));
    }

    #[Test]
    public function user_can_delete_existing(): void
    {
        $this->assertTrue($this->policy->delete(User::factory()->create(), $this->existingModel()));
    }

    #[Test]
    public function user_can_restore_existing(): void
    {
        $this->assertTrue($this->policy->restore(User::factory()->create(), $this->existingModel()));
    }

    #[Test]
    public function user_can_force_delete_existing(): void
    {
        $this->assertTrue($this->policy->forceDelete(User::factory()->create(), $this->existingModel()));
    }
}
