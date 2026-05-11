<?php

namespace Tests\Unit\Modules\Contacts\Services;

use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\Contacts\Services\ContactService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ContactServiceTest extends TestCase
{
    private ContactService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContactService::class);
    }

    #[Test]
    public function create_for_model_persists_contact(): void
    {
        $provider = ServiceProvider::factory()->create();

        $contact = $this->service->createForModel($provider, [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+49 30 123456',
            'is_primary' => true,
        ]);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertTrue($contact->exists);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'contactable_type' => ServiceProvider::class,
            'contactable_id' => $provider->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    #[Test]
    public function update_for_model_changes_fields(): void
    {
        $provider = ServiceProvider::factory()->create();
        $contact = $this->service->createForModel($provider, [
            'name' => 'Old name',
            'email' => 'old@example.com',
        ]);

        $result = $this->service->updateForModel($contact, [
            'name' => 'New name',
            'email' => 'new@example.com',
        ]);

        $this->assertTrue($result);
        $fresh = $contact->fresh();
        $this->assertSame('New name', $fresh->name);
        $this->assertSame('new@example.com', $fresh->email);
    }

    #[Test]
    public function delete_from_model_removes_contact(): void
    {
        $provider = ServiceProvider::factory()->create();
        $contact = $this->service->createForModel($provider, ['name' => 'To delete']);

        $result = $this->service->deleteFromModel($contact);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function resolve_model_instance_resolves_user(): void
    {
        $user = User::factory()->create();

        $resolved = $this->service->resolveModelInstance('user', $user->id);

        $this->assertInstanceOf(User::class, $resolved);
        $this->assertSame($user->id, $resolved->id);
    }

    #[Test]
    public function resolve_model_instance_resolves_provider(): void
    {
        $provider = ServiceProvider::factory()->create();

        $resolved = $this->service->resolveModelInstance('provider', $provider->id);

        $this->assertInstanceOf(ServiceProvider::class, $resolved);
        $this->assertSame($provider->id, $resolved->id);
    }

    #[Test]
    public function resolve_model_instance_aborts_on_unknown_model(): void
    {
        $this->expectException(HttpException::class);

        $this->service->resolveModelInstance('unknown', 1);
    }
}
