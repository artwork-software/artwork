<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der CRM-Einstellungsseite und der Kontakttyp-Endpunkte
 * (anlegen, ändern, sortieren, Eigenschaften zuordnen, löschen) fest. Alle Routen
 * liegen in der Gruppe "crm/settings" hinter `can:crm manager`.
 */
final class CrmContactTypeSettingsTest extends FeatureTestCase
{
    #[Test]
    public function settings_page_renders_contact_types_and_property_groups_for_crm_manager(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        $property = CrmProperty::factory()->create();

        $this->get(route('crm.settings.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CRM/Settings/Index')
                ->has('contactTypes', 1)
                ->where('contactTypes.0.id', $type->id)
                ->has('propertyGroups', 1)
                ->where('propertyGroups.0.id', $property->crm_property_group_id));
    }

    #[Test]
    public function settings_page_is_forbidden_for_crm_viewer_and_plain_user(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->get(route('crm.settings.index'))->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->get(route('crm.settings.index'))->assertForbidden();
    }

    #[Test]
    public function settings_page_redirects_guests_to_login(): void
    {
        $this->get(route('crm.settings.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function store_creates_type_with_slug_from_name_and_syncs_properties(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create();

        $this->from(route('crm.settings.index'))
            ->post(route('crm.types.store'), [
                'name' => 'Agentur Partner',
                'icon' => 'building',
                'color' => '#ff0000',
                'properties' => [
                    ['id' => $property->id, 'is_required' => true, 'show_in_list' => true, 'sort_order' => 3],
                ],
            ])
            ->assertRedirect(route('crm.settings.index'));

        $type = CrmContactType::query()->where('name', 'Agentur Partner')->firstOrFail();
        $this->assertSame('agentur-partner', $type->slug);
        $this->assertFalse((bool) $type->is_system);
        $this->assertDatabaseHas('crm_contact_type_property', [
            'crm_contact_type_id' => $type->id,
            'crm_property_id' => $property->id,
            'is_required' => true,
            'show_in_list' => true,
            'is_filterable' => false,
            'sort_order' => 3,
        ]);
    }

    #[Test]
    public function store_requires_a_name_and_existing_property_ids(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->post(route('crm.types.store'), [
            'properties' => [['id' => 999999]],
        ])->assertSessionHasErrors(['name', 'properties.0.id']);

        $this->assertDatabaseCount('crm_contact_types', 0);
    }

    #[Test]
    public function store_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->post(route('crm.types.store'), ['name' => 'Nope'])->assertForbidden();

        $this->assertDatabaseMissing('crm_contact_types', ['name' => 'Nope']);
    }

    #[Test]
    public function store_with_a_name_whose_slug_already_exists_gets_a_numbered_slug(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $first = CrmContactType::factory()->create(['name' => 'Agentur', 'slug' => 'agentur']);
        $first->delete();

        // vorher 500: UNIQUE-Constraint auf slug, auch gegenüber gelöschten Typen
        $this->post(route('crm.types.store'), ['name' => 'Agentur'])->assertRedirect();
        $this->post(route('crm.types.store'), ['name' => 'Agentur'])->assertRedirect();

        $this->assertSame(
            ['agentur-2', 'agentur-3'],
            CrmContactType::query()->where('name', 'Agentur')->orderBy('id')->pluck('slug')->all()
        );
    }

    #[Test]
    public function update_changes_attributes_and_resyncs_properties_when_given(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create(['name' => 'Alt', 'slug' => 'alt']);
        $oldProperty = CrmProperty::factory()->create();
        $newProperty = CrmProperty::factory()->create();
        $type->properties()->attach($oldProperty->id, ['sort_order' => 0]);

        $this->patch(route('crm.types.update', $type), [
            'name' => 'Neu',
            'is_active' => false,
            'sort_order' => 7,
            'properties' => [['id' => $newProperty->id, 'is_filterable' => true]],
        ])->assertRedirect();

        $type->refresh();
        $this->assertSame('Neu', $type->name);
        $this->assertSame('alt', $type->slug, 'Slug bleibt beim Umbenennen unverändert');
        $this->assertFalse($type->is_active);
        $this->assertSame(7, (int) $type->sort_order);
        $this->assertSame([$newProperty->id], $type->properties()->pluck('crm_properties.id')->all());
    }

    #[Test]
    public function update_without_properties_key_keeps_existing_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        $property = CrmProperty::factory()->create();
        $type->properties()->attach($property->id, ['sort_order' => 0]);

        $this->patch(route('crm.types.update', $type), ['name' => 'Umbenannt'])->assertRedirect();

        $this->assertSame([$property->id], $type->properties()->pluck('crm_properties.id')->all());
    }

    #[Test]
    public function update_allows_renaming_and_deactivating_system_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create(['is_system' => true, 'slug' => 'system-typ']);

        $this->patch(route('crm.types.update', $type), ['name' => 'Umbenannt', 'is_active' => false])
            ->assertRedirect();

        $type->refresh();
        $this->assertSame('Umbenannt', $type->name);
        $this->assertSame('system-typ', $type->slug);
        $this->assertFalse($type->is_active);
    }

    #[Test]
    public function update_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $type = CrmContactType::factory()->create(['name' => 'Bleibt']);

        $this->patch(route('crm.types.update', $type), ['name' => 'Geändert'])->assertForbidden();

        $this->assertSame('Bleibt', $type->fresh()->name);
    }

    #[Test]
    public function reorder_updates_sort_order_of_all_given_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $first = CrmContactType::factory()->create(['sort_order' => 0]);
        $second = CrmContactType::factory()->create(['sort_order' => 1]);

        $this->patch(route('crm.types.reorder'), [
            'types' => [
                ['id' => $first->id, 'sort_order' => 1],
                ['id' => $second->id, 'sort_order' => 0],
            ],
        ])->assertRedirect();

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(0, (int) $second->fresh()->sort_order);
    }

    #[Test]
    public function reorder_validates_payload_and_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->patch(route('crm.types.reorder'), ['types' => [['id' => 999999]]])
            ->assertSessionHasErrors(['types.0.id', 'types.0.sort_order']);

        $type = CrmContactType::factory()->create(['sort_order' => 4]);
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->patch(route('crm.types.reorder'), ['types' => [['id' => $type->id, 'sort_order' => 0]]])
            ->assertForbidden();
        $this->assertSame(4, (int) $type->fresh()->sort_order);
    }

    #[Test]
    public function sync_properties_replaces_pivot_with_defaults_for_missing_flags(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();
        $kept = CrmProperty::factory()->create();
        $removed = CrmProperty::factory()->create();
        $type->properties()->attach([
            $kept->id => ['sort_order' => 0, 'is_required' => true],
            $removed->id => ['sort_order' => 1],
        ]);

        $this->patch(route('crm.types.sync-properties', $type), [
            'properties' => [['id' => $kept->id]],
        ])->assertRedirect();

        $this->assertDatabaseHas('crm_contact_type_property', [
            'crm_contact_type_id' => $type->id,
            'crm_property_id' => $kept->id,
            'is_required' => false,
            'show_in_list' => false,
            'is_filterable' => false,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseMissing('crm_contact_type_property', [
            'crm_contact_type_id' => $type->id,
            'crm_property_id' => $removed->id,
        ]);
    }

    #[Test]
    public function sync_properties_keeps_system_properties_of_system_types_even_if_omitted(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create(['is_system' => true]);
        $systemProperty = CrmProperty::factory()->create(['is_system' => true]);
        $customProperty = CrmProperty::factory()->create();
        $type->properties()->attach([
            $systemProperty->id => ['sort_order' => 2, 'is_required' => true, 'show_in_list' => true],
            $customProperty->id => ['sort_order' => 3],
        ]);

        $this->patch(route('crm.types.sync-properties', $type), ['properties' => []])->assertRedirect();

        $this->assertDatabaseHas('crm_contact_type_property', [
            'crm_contact_type_id' => $type->id,
            'crm_property_id' => $systemProperty->id,
            'is_required' => true,
            'show_in_list' => true,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseMissing('crm_contact_type_property', [
            'crm_contact_type_id' => $type->id,
            'crm_property_id' => $customProperty->id,
        ]);
    }

    #[Test]
    public function sync_properties_requires_properties_key_and_is_forbidden_for_crm_viewer(): void
    {
        $type = CrmContactType::factory()->create();
        $property = CrmProperty::factory()->create();
        $type->properties()->attach($property->id, ['sort_order' => 0]);

        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->patch(route('crm.types.sync-properties', $type), [])->assertSessionHasErrors('properties');

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->patch(route('crm.types.sync-properties', $type), ['properties' => []])->assertForbidden();

        $this->assertSame([$property->id], $type->properties()->pluck('crm_properties.id')->all());
    }

    #[Test]
    public function destroy_soft_deletes_a_custom_type_without_contacts(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $type = CrmContactType::factory()->create();

        $this->delete(route('crm.types.destroy', $type))->assertRedirect();

        $this->assertSoftDeleted('crm_contact_types', ['id' => $type->id]);
    }

    #[Test]
    public function destroy_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $type = CrmContactType::factory()->create();

        $this->delete(route('crm.types.destroy', $type))->assertForbidden();

        $this->assertNotSoftDeleted('crm_contact_types', ['id' => $type->id]);
    }

    #[Test]
    public function destroy_refuses_system_types_and_types_with_contacts(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $systemType = CrmContactType::factory()->create(['is_system' => true]);
        $usedType = CrmContactType::factory()->create();
        CrmContact::factory()->create(['crm_contact_type_id' => $usedType->id]);

        $this->deleteJson(route('crm.types.destroy', $systemType))
            ->assertUnprocessable()
            ->assertJson(['message' => 'System contact types cannot be deleted.']);

        $this->deleteJson(route('crm.types.destroy', $usedType))
            ->assertUnprocessable()
            ->assertJson(['message' => 'Contact types with assigned contacts cannot be deleted.']);

        $this->assertNotSoftDeleted('crm_contact_types', ['id' => $systemType->id]);
        $this->assertNotSoftDeleted('crm_contact_types', ['id' => $usedType->id]);
    }
}
