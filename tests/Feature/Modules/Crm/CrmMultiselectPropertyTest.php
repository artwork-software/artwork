<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Repositories\CrmContactRepository;
use Artwork\Modules\Crm\Support\CrmPropertyValueFormat;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * CRM-Eigenschaftstyp Mehrfachauswahl: Wert als JSON-Liste, Filter findet einzelne Optionen (ODER),
 * Import mit ";", Einfachauswahl → Mehrfachauswahl umwandelbar, zurück nicht.
 */
final class CrmMultiselectPropertyTest extends FeatureTestCase
{
    #[Test]
    public function multiselect_property_can_be_created_with_display_mode(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create();

        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $group->id,
            'name' => 'Sparten',
            'type' => 'multiselect',
            'select_values' => ['Tanz', 'Theater'],
            'multiselect_display' => 'dropdown',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $property = CrmProperty::query()->where('name', 'Sparten')->firstOrFail();
        $this->assertSame(CrmPropertyTypeEnum::MULTISELECT, $property->type);
        $this->assertSame('dropdown', $property->multiselect_display);
    }

    #[Test]
    public function contact_values_are_stored_as_json_list_of_known_options(): void
    {
        $this->actingAsAdmin();
        $property = $this->multiselectProperty();
        $contact = CrmContact::factory()->create();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => '["Theater","Unbekannt","Tanz"]'],
        ])->assertRedirect();
        $this->assertSame('["Tanz","Theater"]', $this->storedValue($contact, $property));

        // Liste statt JSON-Text wird ebenso angenommen
        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => ['Theater']],
        ])->assertRedirect();
        $this->assertSame('["Theater"]', $this->storedValue($contact, $property));

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => ''],
        ])->assertRedirect();
        $this->assertNull($this->storedValue($contact, $property));
    }

    #[Test]
    public function filter_finds_contacts_containing_any_selected_option(): void
    {
        $property = $this->multiselectProperty();
        $dance = $this->contactWith($property, '["Tanz"]');
        $both = $this->contactWith($property, '["Tanz","Theater"]');
        $theatre = $this->contactWith($property, '["Theater"]');

        $query = CrmContact::query();
        app(CrmContactRepository::class)->applyPropertyFilters($query, [$property->id => ['Tanz']]);
        $this->assertEqualsCanonicalizing([$dance->id, $both->id], $query->pluck('id')->all());

        $query = CrmContact::query();
        app(CrmContactRepository::class)->applyPropertyFilters($query, [$property->id => ['Tanz', 'Theater']]);
        $this->assertEqualsCanonicalizing([$dance->id, $both->id, $theatre->id], $query->pluck('id')->all());
    }

    #[Test]
    public function import_accepts_semicolon_separated_known_options_only(): void
    {
        $property = $this->multiselectProperty();

        $this->assertSame('["Tanz","Theater"]', CrmPropertyValueFormat::fromImport($property, 'Theater; Tanz'));
        $this->assertFalse(CrmPropertyValueFormat::fromImport($property, 'Tanz; Oper'));
    }

    #[Test]
    public function single_choice_can_become_multiple_choice_but_not_back(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create([
            'type' => CrmPropertyTypeEnum::SELECT->value,
            'select_values' => ['Tanz', 'Theater'],
        ]);
        $contact = $this->contactWith($property, 'Theater');

        $this->patch(route('crm.properties.update', $property), ['type' => 'multiselect'])
            ->assertSessionHasNoErrors();
        $this->assertSame('["Theater"]', $this->storedValue($contact, $property));

        $this->patch(route('crm.properties.update', $property), ['type' => 'select'])
            ->assertSessionHasErrors('type');
        $this->assertSame(CrmPropertyTypeEnum::MULTISELECT, $property->fresh()->type);

        $text = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);
        $this->patch(route('crm.properties.update', $text), ['type' => 'multiselect'])
            ->assertSessionHasErrors('type');
    }

    #[Test]
    public function converting_to_multiple_choice_keeps_values_of_renamed_options(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $property = CrmProperty::factory()->create([
            'type' => CrmPropertyTypeEnum::SELECT->value,
            'select_values' => ['Tanz', 'Theater'],
        ]);
        $renamed = $this->contactWith($property, 'Tanz');
        $empty = $this->contactWith($property, '');

        $this->patch(route('crm.properties.update', $property), [
            'type' => 'multiselect',
            'select_values' => ['Dance', 'Theater'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('["Tanz"]', $this->storedValue($renamed, $property));
        $this->assertNull($this->storedValue($empty, $property));
    }

    #[Test]
    public function display_text_joins_options(): void
    {
        $this->assertSame('Tanz, Theater', CrmPropertyValueFormat::displayText('multiselect', '["Tanz","Theater"]'));
        $this->assertSame('Tanz', CrmPropertyValueFormat::displayText('multiselect', 'Tanz'));
        $this->assertSame('frei', CrmPropertyValueFormat::displayText('text', 'frei'));
    }

    private function multiselectProperty(): CrmProperty
    {
        return CrmProperty::factory()->create([
            'type' => CrmPropertyTypeEnum::MULTISELECT->value,
            'select_values' => ['Tanz', 'Theater'],
        ]);
    }

    private function contactWith(CrmProperty $property, string $value): CrmContact
    {
        $contact = CrmContact::factory()->create();
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $property->id,
            'value' => $value,
        ]);

        return $contact;
    }

    private function storedValue(CrmContact $contact, CrmProperty $property): ?string
    {
        return CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $property->id)
            ->value('value');
    }
}
