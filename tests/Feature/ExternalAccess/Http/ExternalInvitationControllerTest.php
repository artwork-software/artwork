<?php

namespace Tests\Feature\ExternalAccess\Http;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalInvitationControllerTest extends TestCase
{
    private function contactType(string $slug = 'freelancer'): CrmContactType
    {
        return CrmContactType::query()->create(['name' => ucfirst($slug), 'slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        // Create the default freelancer type only when the test did not supply one.
        $contactTypeId = $overrides['crm_contact_type_id'] ?? $this->contactType()->id;

        return array_merge([
            'email' => 'invitee-' . uniqid() . '@example.test',
            'crm_contact_type_id' => $contactTypeId,
            'source' => InviteSource::CRM_INDEX->value,
            'public_field_values' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
        ], $overrides);
    }

    #[Test]
    public function user_without_permission_gets_403(): void
    {
        $this->actingAsUserWith([]); // seeds, but grants nothing
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload());

        $response->assertForbidden();
    }

    #[Test]
    public function inviting_returns_201_on_success(): void
    {
        Notification::fake();
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType();

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'email' => 'success@example.test',
            'crm_contact_type_id' => $type->id,
        ]));

        $response->assertCreated()->assertJsonStructure(['message', 'external_access_id']);
        $this->assertDatabaseHas('external_accesses', ['email' => 'success@example.test']);
    }

    #[Test]
    public function inviting_an_internal_user_email_returns_422(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType();
        User::factory()->create(['email' => 'staff@example.test']);

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'email' => 'staff@example.test',
            'crm_contact_type_id' => $type->id,
        ]));

        $response->assertStatus(422);
    }

    #[Test]
    public function inviting_from_a_project_without_access_returns_422(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'crm_contact_type_id' => $type->id,
            'source' => InviteSource::PROJECT_TAB->value,
            'source_reference_project_id' => $project->id,
            'tab_scopes' => [[
                'project_tab_id' => $tab->id,
                'access_type' => ExternalAccessType::READ->value,
                'valid_from' => now()->toDateString(),
                'valid_to' => now()->addMonth()->toDateString(),
            ]],
        ]));

        $response->assertStatus(422);
        $this->assertSame(0, ExternalAccess::query()->count());
    }

    #[Test]
    public function admin_can_invite_without_explicit_permission(): void
    {
        Notification::fake();
        $this->actingAsAdmin();
        $type = $this->contactType();

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'email' => 'admininvite@example.test',
            'crm_contact_type_id' => $type->id,
        ]));

        $response->assertCreated();
    }

    #[Test]
    public function contact_type_requirements_endpoint_returns_required_fields(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType('freelancer');

        $response = $this->getJson(
            route('crm.externals.contact-types.requirements', ['crmContactType' => $type->id])
        );

        $response->assertOk()
            ->assertJson([
                'invitable' => true,
                'slug' => 'freelancer',
                'public_required_fields' => ['first_name', 'last_name'],
            ]);
    }
}
