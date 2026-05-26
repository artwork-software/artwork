<?php

namespace Tests\Unit\Policies;

use App\Policies\ContactPolicy;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\User\Models\User;
use Database\Factories\ContactFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ContactPolicyTest extends TestCase
{
    private ContactPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ContactPolicy::class);
    }

    private function createContact(): Contact
    {
        return (new ContactFactory())->create();
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
    public function user_can_view_existing_contact(): void
    {
        $this->assertTrue($this->policy->view(User::factory()->create(), $this->createContact()));
    }

    #[Test]
    public function user_cannot_view_unsaved_contact(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new Contact()));
    }

    #[Test]
    public function user_can_create_contact(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_can_update_existing_contact(): void
    {
        $this->assertTrue($this->policy->update(User::factory()->create(), $this->createContact()));
    }

    #[Test]
    public function user_can_delete_existing_contact(): void
    {
        $this->assertTrue($this->policy->delete(User::factory()->create(), $this->createContact()));
    }

    #[Test]
    public function user_can_restore_existing_contact(): void
    {
        $this->assertTrue($this->policy->restore(User::factory()->create(), $this->createContact()));
    }

    #[Test]
    public function user_can_force_delete_existing_contact(): void
    {
        $this->assertTrue($this->policy->forceDelete(User::factory()->create(), $this->createContact()));
    }
}
