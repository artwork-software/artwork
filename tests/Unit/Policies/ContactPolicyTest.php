<?php

namespace Tests\Unit\Policies;

use App\Policies\ContactPolicy;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Database\Factories\ContactFactory;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Kontaktdaten dürfen nur von denen gepflegt werden, die die Eltern-Entität pflegen dürfen.
 */
final class ContactPolicyTest extends TestCase
{
    private ContactPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ContactPolicy::class);
    }

    private function createContact(array $attributes = []): Contact
    {
        return (new ContactFactory())->create($attributes);
    }

    private function grant(User $user, PermissionEnum $permission): User
    {
        Permission::findOrCreate($permission->value, 'web');
        $user->givePermissionTo($permission->value);

        return $user;
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
    public function create_without_parent_is_denied(): void
    {
        $this->assertFalse($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_may_manage_contacts_of_own_profile_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = $this->createContact(['contactable_id' => $user->id]);
        $foreign = $this->createContact(['contactable_id' => $other->id]);

        $this->assertTrue($this->policy->create($user, $user));
        $this->assertTrue($this->policy->update($user, $own));
        $this->assertTrue($this->policy->delete($user, $own));

        $this->assertFalse($this->policy->create($user, $other));
        $this->assertFalse($this->policy->update($user, $foreign));
        $this->assertFalse($this->policy->delete($user, $foreign));
        $this->assertFalse($this->policy->restore($user, $foreign));
        $this->assertFalse($this->policy->forceDelete($user, $foreign));
    }

    #[Test]
    public function service_provider_contacts_require_external_management_permission(): void
    {
        $provider = ServiceProvider::factory()->create();
        $contact = $this->createContact([
            'contactable_type' => ServiceProvider::class,
            'contactable_id' => $provider->id,
        ]);

        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user, $provider));
        $this->assertFalse($this->policy->update($user, $contact));

        $manager = $this->grant(User::factory()->create(), PermissionEnum::EXTERNAL_MANAGER);
        $this->assertTrue($this->policy->create($manager, $provider));
        $this->assertTrue($this->policy->update($manager, $contact));
        $this->assertTrue($this->policy->delete($manager, $contact));
    }

    #[Test]
    public function accommodation_contacts_follow_the_accommodation_policy(): void
    {
        $accommodation = Accommodation::factory()->create();
        $contact = $this->createContact([
            'contactable_type' => Accommodation::class,
            'contactable_id' => $accommodation->id,
        ]);

        $user = User::factory()->create();
        $this->assertFalse($this->policy->create($user, $accommodation));
        $this->assertFalse($this->policy->update($user, $contact));

        $writer = User::factory()->create();
        Project::factory()->create()->users()->attach($writer, ['can_write' => true]);
        $this->assertTrue($this->policy->create($writer, $accommodation));
        $this->assertTrue($this->policy->update($writer, $contact));
        $this->assertTrue($this->policy->delete($writer, $contact));
    }
}
