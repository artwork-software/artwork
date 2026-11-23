<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eine Eigenschaft erscheint auf der Kontaktseite nur, wenn sie dem Kontakttyp zugeordnet ist. Neue
 * Eigenschaften landen deshalb automatisch bei allen Typen, die ihre Gruppe schon nutzen; die Zuordnung
 * lässt sich beim Anlegen und Bearbeiten auch ausdrücklich wählen.
 */
final class CrmPropertyContactTypeAssignmentTest extends FeatureTestCase
{
    private CrmPropertyGroup $group;

    private CrmProperty $existingProperty;

    private CrmContactType $typeUsingGroup;

    private CrmContactType $otherType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->group = CrmPropertyGroup::factory()->create();
        $this->existingProperty = CrmProperty::factory()->create([
            'crm_property_group_id' => $this->group->id,
            'sort_order' => 4,
        ]);
        $foreignProperty = CrmProperty::factory()->create();

        $this->typeUsingGroup = CrmContactType::factory()->create();
        $this->typeUsingGroup->properties()->attach([
            $this->existingProperty->id => ['sort_order' => 2, 'is_required' => true],
            $foreignProperty->id => ['sort_order' => 5],
        ]);

        $this->otherType = CrmContactType::factory()->create();
        $this->otherType->properties()->attach([$foreignProperty->id => ['sort_order' => 7]]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function storeProperty(array $overrides = []): CrmProperty
    {
        $this->post(route('crm.properties.store'), array_merge([
            'crm_property_group_id' => $this->group->id,
            'name' => 'Vertrag',
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
        ], $overrides))->assertRedirect()->assertSessionHasNoErrors();

        return CrmProperty::query()->where('name', 'Vertrag')->firstOrFail();
    }

    /**
     * @return array<int, int>
     */
    private function assignedTypeIds(CrmProperty $property): array
    {
        return $property->contactTypes()->pluck('crm_contact_types.id')->sort()->values()->all();
    }

    #[Test]
    public function new_property_is_assigned_to_contact_types_already_using_its_group(): void
    {
        $property = $this->storeProperty();

        $this->assertSame([$this->typeUsingGroup->id], $this->assignedTypeIds($property));

        $pivot = $this->typeUsingGroup->properties()->whereKey($property->id)->firstOrFail()->pivot;
        $this->assertSame(2, (int) $pivot->sort_order, 'reiht sich bei der eigenen Gruppe ein');
        $this->assertFalse((bool) $pivot->is_required);
    }

    #[Test]
    public function new_property_is_visible_on_contact_page_of_that_type(): void
    {
        $property = $this->storeProperty();

        $typeProperties = CrmContactType::with('properties')->findOrFail($this->typeUsingGroup->id)->properties;

        $this->assertTrue($typeProperties->contains('id', $property->id));
    }

    #[Test]
    public function new_property_is_appended_to_the_end_of_its_group(): void
    {
        $property = $this->storeProperty();

        $this->assertSame(5, (int) $property->sort_order);
    }

    #[Test]
    public function store_respects_an_explicit_contact_type_selection(): void
    {
        $property = $this->storeProperty(['contact_type_ids' => [$this->otherType->id]]);

        $this->assertSame([$this->otherType->id], $this->assignedTypeIds($property));
        $this->assertSame(
            8,
            (int) $this->otherType->properties()->whereKey($property->id)->firstOrFail()->pivot->sort_order,
            'Gruppe bisher ungenutzt: ans Ende des Typs'
        );
    }

    #[Test]
    public function store_with_empty_selection_assigns_no_contact_type(): void
    {
        $property = $this->storeProperty(['contact_type_ids' => []]);

        $this->assertSame([], $this->assignedTypeIds($property));
    }

    #[Test]
    public function store_rejects_unknown_contact_types(): void
    {
        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $this->group->id,
            'name' => 'Vertrag',
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
            'contact_type_ids' => [999999],
        ])->assertSessionHasErrors('contact_type_ids.0');

        $this->assertDatabaseMissing('crm_properties', ['name' => 'Vertrag']);
    }

    #[Test]
    public function update_syncs_contact_types_and_keeps_existing_pivot_options(): void
    {
        $this->patch(route('crm.properties.update', $this->existingProperty), [
            'contact_type_ids' => [$this->typeUsingGroup->id, $this->otherType->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(
            collect([$this->typeUsingGroup->id, $this->otherType->id])->sort()->values()->all(),
            $this->assignedTypeIds($this->existingProperty)
        );
        $this->assertTrue(
            (bool) $this->typeUsingGroup->properties()->whereKey($this->existingProperty->id)->firstOrFail()
                ->pivot->is_required
        );

        $this->patch(route('crm.properties.update', $this->existingProperty), [
            'contact_type_ids' => [],
        ])->assertRedirect();

        $this->assertSame([], $this->assignedTypeIds($this->existingProperty));
    }

    #[Test]
    public function update_without_contact_type_ids_leaves_the_assignment_untouched(): void
    {
        $this->patch(route('crm.properties.update', $this->existingProperty), [
            'name' => 'Umbenannt',
        ])->assertRedirect();

        $this->assertSame([$this->typeUsingGroup->id], $this->assignedTypeIds($this->existingProperty));
    }

    #[Test]
    public function system_property_stays_on_system_contact_types(): void
    {
        $systemType = CrmContactType::factory()->create(['is_system' => true]);
        $systemProperty = CrmProperty::factory()->create([
            'crm_property_group_id' => $this->group->id,
            'is_system' => true,
        ]);
        $systemProperty->contactTypes()->attach([
            $systemType->id => ['sort_order' => 1],
            $this->otherType->id => ['sort_order' => 9],
        ]);

        $this->patch(route('crm.properties.update', $systemProperty), [
            'contact_type_ids' => [],
        ])->assertRedirect();

        $this->assertSame([$systemType->id], $this->assignedTypeIds($systemProperty));
    }

    #[Test]
    public function tooltip_longer_than_the_column_is_rejected_instead_of_failing(): void
    {
        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $this->group->id,
            'name' => 'Vertrag',
            'type' => CrmPropertyTypeEnum::TEXT->value,
            'tooltip_text' => str_repeat('a', 256),
        ])->assertSessionHasErrors('tooltip_text');
    }
}
