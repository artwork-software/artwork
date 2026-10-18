<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Eigenschafts-Endpunkte (anlegen, ändern, sortieren,
 * löschen) unter "crm/settings/properties" fest; nur für `crm manager`.
 */
final class CrmPropertySettingsTest extends FeatureTestCase
{
    #[Test]
    public function store_creates_property_in_group(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create();

        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $group->id,
            'name' => 'Lieblingsfarbe',
            'type' => CrmPropertyTypeEnum::SELECT->value,
            'select_values' => ['rot', 'blau'],
            'tooltip_text' => 'Hilfe',
        ])->assertRedirect();

        $property = CrmProperty::query()->where('name', 'Lieblingsfarbe')->firstOrFail();
        $this->assertSame($group->id, $property->crm_property_group_id);
        $this->assertSame(CrmPropertyTypeEnum::SELECT, $property->type);
        $this->assertSame(['rot', 'blau'], $property->select_values);
        $this->assertSame('Hilfe', $property->tooltip_text);
        $this->assertFalse((bool) $property->is_system);
    }

    #[Test]
    public function store_validates_group_name_and_type(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => 999999,
            'type' => 'unbekannt',
        ])->assertSessionHasErrors(['crm_property_group_id', 'name', 'type']);

        $this->assertDatabaseCount('crm_properties', 0);
    }

    #[Test]
    public function store_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $group = CrmPropertyGroup::factory()->create();

        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $group->id,
            'name' => 'Nope',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ])->assertForbidden();

        $this->assertDatabaseCount('crm_properties', 0);
    }

    #[Test]
    public function update_changes_name_type_and_sort_order(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);

        $this->patch(route('crm.properties.update', $property), [
            'name' => 'Geburtstag',
            'type' => CrmPropertyTypeEnum::DATE->value,
            'sort_order' => 9,
        ])->assertRedirect();

        $property->refresh();
        $this->assertSame('Geburtstag', $property->name);
        $this->assertSame(CrmPropertyTypeEnum::DATE, $property->type);
        $this->assertSame(9, (int) $property->sort_order);
    }

    #[Test]
    public function update_ignores_group_reassignment_and_rejects_unknown_type(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create();
        $otherGroup = CrmPropertyGroup::factory()->create();
        $originalGroupId = $property->crm_property_group_id;

        $this->patch(route('crm.properties.update', $property), ['crm_property_group_id' => $otherGroup->id])
            ->assertRedirect();
        $this->assertSame($originalGroupId, $property->fresh()->crm_property_group_id);

        $this->patch(route('crm.properties.update', $property), ['type' => 'unbekannt'])
            ->assertSessionHasErrors('type');
    }

    #[Test]
    public function update_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $property = CrmProperty::factory()->create(['name' => 'Bleibt']);

        $this->patch(route('crm.properties.update', $property), ['name' => 'Geändert'])->assertForbidden();

        $this->assertSame('Bleibt', $property->fresh()->name);
    }

    #[Test]
    public function reorder_updates_sort_order_and_validates_ids(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $first = CrmProperty::factory()->create(['sort_order' => 0]);
        $second = CrmProperty::factory()->create(['sort_order' => 1]);

        $this->patch(route('crm.properties.reorder'), [
            'properties' => [
                ['id' => $first->id, 'sort_order' => 1],
                ['id' => $second->id, 'sort_order' => 0],
            ],
        ])->assertRedirect();

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(0, (int) $second->fresh()->sort_order);

        $this->patch(route('crm.properties.reorder'), ['properties' => [['id' => 999999, 'sort_order' => 0]]])
            ->assertSessionHasErrors('properties.0.id');
    }

    #[Test]
    public function reorder_is_forbidden_for_crm_viewer(): void
    {
        $property = CrmProperty::factory()->create(['sort_order' => 2]);
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->patch(route('crm.properties.reorder'), [
            'properties' => [['id' => $property->id, 'sort_order' => 0]],
        ])->assertForbidden();

        $this->assertSame(2, (int) $property->fresh()->sort_order);
    }

    #[Test]
    public function destroy_deletes_property_with_its_values_and_type_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        $property = CrmProperty::factory()->create();
        $type->properties()->attach($property->id, ['sort_order' => 0]);
        $contact = CrmContact::factory()->create(['crm_contact_type_id' => $type->id]);
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => 'x',
        ]);

        $this->delete(route('crm.properties.destroy', $property))->assertRedirect();

        $this->assertDatabaseMissing('crm_properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('crm_property_values', ['crm_property_id' => $property->id]);
        $this->assertDatabaseMissing('crm_contact_type_property', ['crm_property_id' => $property->id]);
    }

    #[Test]
    public function destroy_refuses_system_properties(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create(['is_system' => true]);

        $this->deleteJson(route('crm.properties.destroy', $property))
            ->assertUnprocessable()
            ->assertJson(['message' => 'System properties cannot be deleted.']);

        $this->assertDatabaseHas('crm_properties', ['id' => $property->id]);
    }

    #[Test]
    public function destroy_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $property = CrmProperty::factory()->create();

        $this->delete(route('crm.properties.destroy', $property))->assertForbidden();

        $this->assertDatabaseHas('crm_properties', ['id' => $property->id]);
    }

    #[Test]
    public function system_properties_cannot_be_renamed_or_retyped(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $email = CrmProperty::factory()->create([
            'name' => 'Email',
            'type' => CrmPropertyTypeEnum::TEXT->value,
            'is_system' => true,
        ]);

        // E-Mail-Duplikatsuche und Tooltip finden die Eigenschaft über ihren Namen
        $this->patchJson(route('crm.properties.update', $email), ['name' => 'X'])->assertUnprocessable();
        $this->patchJson(route('crm.properties.update', $email), ['type' => CrmPropertyTypeEnum::CHECKBOX->value])
            ->assertUnprocessable();
        $this->patch(route('crm.properties.update', $email), ['tooltip_text' => 'Hinweis'])->assertRedirect();

        $email->refresh();
        $this->assertSame('Email', $email->name);
        $this->assertSame(CrmPropertyTypeEnum::TEXT, $email->type);
        $this->assertSame('Hinweis', $email->tooltip_text);
    }
}
