<?php

namespace Tests\Feature\ExternalAccess\Http;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
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

    #[Test]
    public function freely_created_contact_type_is_invitable_and_only_requires_a_display_name(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        // A slug that is NOT registered as a system source-entity factory.
        $type = $this->contactType('sponsor');

        $response = $this->getJson(
            route('crm.externals.contact-types.requirements', ['crmContactType' => $type->id])
        );

        $response->assertOk()
            ->assertJson([
                'invitable' => true,
                'slug' => 'sponsor',
                'public_required_fields' => ['display_name'],
            ]);
    }

    #[Test]
    public function inviting_a_freely_created_contact_type_creates_a_standalone_crm_contact(): void
    {
        Notification::fake();
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType('sponsor');

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'email' => 'sponsor@example.test',
            'crm_contact_type_id' => $type->id,
            'public_field_values' => ['display_name' => 'Acme Sponsoring GmbH'],
        ]));

        $response->assertCreated()->assertJsonStructure(['message', 'external_access_id']);

        // A standalone CRM contact (no polymorphic source entity) is created and linked.
        $this->assertDatabaseHas('crm_contacts', [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Acme Sponsoring GmbH',
            'entity_type' => null,
            'entity_id' => null,
        ]);
        $this->assertDatabaseHas('external_accesses', ['email' => 'sponsor@example.test']);
    }

    #[Test]
    public function inviting_an_email_held_by_a_standalone_contact_reuses_it_instead_of_duplicating(): void
    {
        Notification::fake();
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = $this->contactType('sponsor');

        // Pre-existing standalone CRM contact (no source entity) whose email lives in the
        // "Email" CRM property value — e.g. created manually in the CRM, never invited yet.
        $group = CrmPropertyGroup::query()->create(['name' => 'Kontaktdaten']);
        $emailProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Email',
            'type' => 'text',
        ]);
        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Acme Sponsoring GmbH',
            'is_active' => true,
        ]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $emailProperty->id,
            'value' => 'reuse@example.test',
        ]);

        $contactsBefore = CrmContact::query()->count();

        $response = $this->postJson(route('crm.externals.invitations.store'), $this->payload([
            'email' => 'reuse@example.test',
            'crm_contact_type_id' => $type->id,
            'public_field_values' => ['display_name' => 'Acme Sponsoring GmbH'],
        ]));

        $response->assertCreated();

        // No duplicate contact created; the external access is linked to the existing one.
        $this->assertSame($contactsBefore, CrmContact::query()->count());
        $this->assertDatabaseHas('external_accesses', [
            'email' => 'reuse@example.test',
            'crm_contact_id' => $contact->id,
        ]);
    }
}
