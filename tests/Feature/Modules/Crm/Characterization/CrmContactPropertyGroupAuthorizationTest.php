<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält fest, wie Eigenschaftsgruppen-Rechte das Schreiben von Kontaktwerten steuern
 * (crm.contacts.store / crm.contacts.update): nicht vertrauliche Gruppen darf jede*r
 * mit `can view crm` befüllen, vertrauliche nur mit `can_edit` (User oder Abteilung)
 * bzw. als `crm manager`. Dazu: Bulk-Löschen überspringt gespiegelte Kontakte.
 */
final class CrmContactPropertyGroupAuthorizationTest extends FeatureTestCase
{
    private CrmContactType $type;

    private CrmProperty $openProperty;

    private CrmProperty $confidentialProperty;

    private CrmPropertyGroup $confidentialGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = CrmContactType::factory()->create();
        $this->openProperty = CrmProperty::factory()->create([
            'crm_property_group_id' => CrmPropertyGroup::factory()->create(['is_confidential' => false])->id,
        ]);
        $this->confidentialGroup = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $this->confidentialProperty = CrmProperty::factory()->create([
            'crm_property_group_id' => $this->confidentialGroup->id,
        ]);
        $this->type->properties()->attach([
            $this->openProperty->id => ['sort_order' => 0],
            $this->confidentialProperty->id => ['sort_order' => 1],
        ]);
    }

    private function grant(string $morphClass, int $id, bool $canEdit): void
    {
        $this->confidentialGroup->permissions()->create([
            'permissionable_type' => $morphClass,
            'permissionable_id' => $id,
            'can_view' => true,
            'can_edit' => $canEdit,
        ]);
    }

    private function storePayload(array $propertyValues): array
    {
        return [
            'crm_contact_type_id' => $this->type->id,
            'display_name' => 'Neue Person',
            'property_values' => $propertyValues,
        ];
    }

    #[Test]
    public function crm_viewer_can_store_values_of_non_confidential_groups(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $response = $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->openProperty->id => 'offen',
        ]));

        $contact = CrmContact::query()->where('display_name', 'Neue Person')->firstOrFail();
        $response->assertRedirect(route('crm.contacts.show', $contact));
        $this->assertTrue($contact->is_active);
        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $this->openProperty->id,
            'value' => 'offen',
        ]);
    }

    #[Test]
    public function store_as_json_returns_201_with_contact_summary(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->postJson(route('crm.contacts.store'), $this->storePayload([]))
            ->assertCreated()
            ->assertJsonPath('display_name', 'Neue Person')
            ->assertJsonPath('contact_type.id', $this->type->id)
            ->assertJsonPath('contact_type.slug', $this->type->slug);
    }

    #[Test]
    public function crm_viewer_without_group_right_cannot_store_confidential_values(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->confidentialProperty->id => 'geheim',
        ]))->assertForbidden();

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Neue Person']);
    }

    #[Test]
    public function view_only_group_right_does_not_allow_storing_confidential_values(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->grant($user->getMorphClass(), $user->id, false);

        $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->confidentialProperty->id => 'geheim',
        ]))->assertForbidden();

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Neue Person']);
    }

    #[Test]
    public function user_edit_right_allows_storing_confidential_values(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->grant($user->getMorphClass(), $user->id, true);

        $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->confidentialProperty->id => 'geheim',
        ]))->assertRedirect();

        $this->assertDatabaseHas('crm_property_values', [
            'crm_property_id' => $this->confidentialProperty->id,
            'value' => 'geheim',
        ]);
    }

    #[Test]
    public function department_edit_right_allows_storing_confidential_values(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $department = Department::factory()->create();
        $user->departments()->attach($department->id);
        $this->grant($department->getMorphClass(), $department->id, true);

        $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->confidentialProperty->id => 'geheim',
        ]))->assertRedirect();

        $this->assertDatabaseHas('crm_property_values', [
            'crm_property_id' => $this->confidentialProperty->id,
            'value' => 'geheim',
        ]);
    }

    #[Test]
    public function crm_manager_can_store_confidential_values_without_group_right(): void
    {
        // "crm manager" impliziert laut Rechte-Katalog "can view crm" (wird beim Speichern mitgesetzt)
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);

        $this->post(route('crm.contacts.store'), $this->storePayload([
            $this->confidentialProperty->id => 'geheim',
        ]))->assertRedirect();

        $this->assertDatabaseHas('crm_property_values', [
            'crm_property_id' => $this->confidentialProperty->id,
            'value' => 'geheim',
        ]);
    }

    #[Test]
    public function user_without_crm_permission_cannot_store_contacts(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('crm.contacts.store'), $this->storePayload([]))->assertForbidden();

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Neue Person']);
    }

    #[Test]
    public function update_of_confidential_values_requires_group_edit_right(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $contact = CrmContact::factory()->create(['crm_contact_type_id' => $this->type->id]);

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$this->confidentialProperty->id => 'geheim'],
        ])->assertForbidden();
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);

        $this->grant($user->getMorphClass(), $user->id, true);

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$this->confidentialProperty->id => 'geheim'],
        ])->assertRedirect();
        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $this->confidentialProperty->id,
            'value' => 'geheim',
        ]);
    }

    #[Test]
    public function bulk_destroy_skips_mirrored_contacts(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $mirroredType = CrmContactType::factory()->create([
            'slug' => CrmSystemContactTypeEnum::SERVICE_PROVIDER->value,
            'is_system' => true,
        ]);
        $mirrored = CrmContact::factory()->create(['crm_contact_type_id' => $mirroredType->id]);
        $free = CrmContact::factory()->create(['crm_contact_type_id' => $this->type->id]);

        $this->post(route('crm.contacts.bulk-destroy'), ['ids' => [$mirrored->id, $free->id]])
            ->assertRedirect();

        $this->assertNotSoftDeleted('crm_contacts', ['id' => $mirrored->id]);
        $this->assertSoftDeleted('crm_contacts', ['id' => $free->id]);
    }

    #[Test]
    public function bulk_destroy_validates_ids(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->post(route('crm.contacts.bulk-destroy'), ['ids' => [999999]])
            ->assertSessionHasErrors('ids.0');
        $this->post(route('crm.contacts.bulk-destroy'), [])
            ->assertSessionHasErrors('ids');
    }
}
