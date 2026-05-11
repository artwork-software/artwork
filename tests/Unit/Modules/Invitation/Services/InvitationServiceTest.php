<?php

namespace Tests\Unit\Modules\Invitation\Services;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Invitation\Services\InvitationService;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvitationServiceTest extends TestCase
{
    private InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvitationService::class);
    }

    #[Test]
    public function find_by_email_returns_invitation_when_present(): void
    {
        $invitation = Invitation::factory()->create(['email' => 'guest@example.test']);

        $result = $this->service->findByEmail('guest@example.test');

        $this->assertNotNull($result);
        $this->assertSame($invitation->id, $result->id);
    }

    #[Test]
    public function find_by_email_returns_null_when_missing(): void
    {
        $this->assertNull($this->service->findByEmail('nobody@example.test'));
    }

    #[Test]
    public function find_by_token_returns_invitation(): void
    {
        $invitation = Invitation::factory()->create(['token' => 'secret-token']);

        $result = $this->service->findByToken('secret-token');

        $this->assertNotNull($result);
        $this->assertSame($invitation->id, $result->id);
    }

    #[Test]
    public function create_persists_invitation(): void
    {
        $attributes = [
            'email' => 'new@example.test',
            'token' => 'tok-123',
            'permissions' => ['can do x'],
            'roles' => ['Editor'],
        ];

        $invitation = $this->service->create($attributes);

        $this->assertInstanceOf(Invitation::class, $invitation);
        $this->assertTrue($invitation->exists);
        $this->assertDatabaseHas('invitations', ['email' => 'new@example.test']);
    }

    #[Test]
    public function update_modifies_invitation_attributes(): void
    {
        $invitation = Invitation::factory()->create(['email' => 'a@b.test']);

        $updated = $this->service->update($invitation, [
            'permissions' => ['foo'],
            'roles' => ['Bar'],
        ]);

        $this->assertSame(['foo'], $updated->fresh()->permissions);
        $this->assertSame(['Bar'], $updated->fresh()->roles);
    }

    #[Test]
    public function sync_departments_attaches_departments(): void
    {
        $invitation = Invitation::factory()->create();
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $this->service->syncDepartments(
            $invitation,
            new SupportCollection([$department1->id, $department2->id])
        );

        $this->assertSame(2, $invitation->departments()->count());
    }
}
