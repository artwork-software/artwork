<?php

namespace Tests\Feature\Modules\Invitation;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreateAdminUser;
use Tests\Feature\FeatureTestCase;

final class InvitationStoreValidationTest extends FeatureTestCase
{
    use CreateAdminUser;

    private MockInterface $mailService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mail::fake() (FeatureTestCase) ersetzt den MailManager durch ein MailFake, an dem der
        // hart typisierte MailService-Konstruktor scheitert — daher den Service selbst spyen.
        $this->mailService = $this->spy(MailService::class);
    }

    #[Test]
    public function it_rejects_roles_that_are_not_defined_in_the_role_enum(): void
    {
        $this->actingAs($this->adminUser());

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => ['artwork admin', 'super duper admin'],
            'permissions' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.1']);

        $this->assertDatabaseCount('invitations', 0);
    }

    #[Test]
    public function it_rejects_a_non_array_roles_payload(): void
    {
        $this->actingAs($this->adminUser());

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => 'artwork admin',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles']);
    }

    #[Test]
    public function it_rejects_permissions_that_are_not_defined_in_the_permission_enum(): void
    {
        $this->actingAs($this->adminUser());

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => [],
            'permissions' => ['definitely not a permission'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);

        $this->assertDatabaseCount('invitations', 0);
    }

    #[Test]
    public function it_stores_an_invitation_with_valid_roles_and_permissions(): void
    {
        $this->actingAs($this->adminUser());

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => [RoleEnum::ARTWORK_ADMIN->value],
            'permissions' => [PermissionEnum::PROJECT_VIEW->value],
        ])->assertRedirect(route('users'));

        $invitation = Invitation::query()->where('email', 'invitee@example.com')->firstOrFail();
        $this->assertSame([RoleEnum::ARTWORK_ADMIN->value], $invitation->roles);
        $this->assertSame([PermissionEnum::PROJECT_VIEW->value], $invitation->permissions);
        $this->mailService->shouldHaveReceived('sendInvitationCreated')->once();
    }
}
