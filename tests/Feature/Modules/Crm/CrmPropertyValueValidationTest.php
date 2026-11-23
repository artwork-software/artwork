<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Services\CrmImportService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Feature\FeatureTestCase;

/**
 * Feldwerte werden beim Anlegen und Bearbeiten eines Kontakts serverseitig geprüft (Typ, Pflicht,
 * Zugehörigkeit zum Kontakttyp); Eigenschaftsnamen sind eindeutig; der Import liest deutsche Zahlen und
 * weist unklare Checkbox-Werte ab.
 */
final class CrmPropertyValueValidationTest extends FeatureTestCase
{
    private CrmContactType $type;

    /** @var array<string, CrmProperty> */
    private array $properties = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);

        $this->type = CrmContactType::factory()->create();
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => false]);
        $definitions = [
            'number' => [CrmPropertyTypeEnum::NUMBER, null, false],
            'date' => [CrmPropertyTypeEnum::DATE, null, false],
            'select' => [CrmPropertyTypeEnum::SELECT, ['rot', 'blau'], false],
            'checkbox' => [CrmPropertyTypeEnum::CHECKBOX, null, false],
            'required' => [CrmPropertyTypeEnum::TEXT, null, true],
        ];
        foreach ($definitions as $key => [$propertyType, $options, $isRequired]) {
            $this->properties[$key] = CrmProperty::factory()->create([
                'crm_property_group_id' => $group->id,
                'type' => $propertyType->value,
                'select_values' => $options,
            ]);
            $this->type->properties()->attach($this->properties[$key]->id, ['is_required' => $isRequired]);
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array<int, mixed>
     */
    private function payload(array $values): array
    {
        $payload = [];
        foreach ($values as $key => $value) {
            $payload[$this->properties[$key]->id] = $value;
        }

        return $payload;
    }

    private function contact(): CrmContact
    {
        return CrmContact::factory()->create(['crm_contact_type_id' => $this->type->id]);
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidValues(): array
    {
        return [
            'Text in Zahl' => ['number', 'abc'],
            'kein Datum' => ['date', '31.12.2026'],
            'unmögliches Datum' => ['date', '2026-02-31'],
            'unbekannte Option' => ['select', 'grün'],
            'geleertes Pflichtfeld' => ['required', '  '],
        ];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function update_rejects_values_that_do_not_fit_the_property(string $key, mixed $value): void
    {
        $contact = $this->contact();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => $this->payload([$key => $value]),
        ])->assertSessionHasErrors('property_values.' . $this->properties[$key]->id);

        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);
    }

    #[Test]
    public function update_accepts_valid_and_emptied_optional_values(): void
    {
        $contact = $this->contact();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => $this->payload([
                'number' => '12.5',
                'date' => '2026-11-19',
                'select' => 'blau',
                'checkbox' => '0',
                'required' => 'da',
            ]),
        ])->assertSessionHasNoErrors();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => $this->payload(['number' => '', 'date' => null, 'select' => '']),
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'da',
            $contact->propertyValues()->where('crm_property_id', $this->properties['required']->id)->value('value')
        );
    }

    #[Test]
    public function values_of_properties_outside_the_type_are_only_checked_for_their_format(): void
    {
        $text = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);
        $number = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::NUMBER->value]);

        $this->patch(route('crm.contacts.update', $this->contact()), [
            'property_values' => [$text->id => 'x'],
        ])->assertSessionHasNoErrors();

        $this->patch(route('crm.contacts.update', $this->contact()), [
            'property_values' => [$number->id => 'x'],
        ])->assertSessionHasErrors('property_values.' . $number->id);
    }

    #[Test]
    public function store_requires_mandatory_properties_even_when_they_are_not_sent(): void
    {
        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $this->type->id,
            'display_name' => 'Ohne Pflichtfeld',
        ])->assertSessionHasErrors('property_values.' . $this->properties['required']->id);

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Ohne Pflichtfeld']);

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $this->type->id,
            'display_name' => 'Mit Pflichtfeld',
            'property_values' => $this->payload(['required' => 'da']),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('crm_contacts', ['display_name' => 'Mit Pflichtfeld']);
    }

    #[Test]
    public function property_names_are_unique_but_existing_duplicates_stay_editable(): void
    {
        $group = CrmPropertyGroup::factory()->create();
        $first = CrmProperty::factory()->create(['name' => 'Notiz', 'crm_property_group_id' => $group->id]);
        $twin = CrmProperty::factory()->create(['name' => 'Notiz', 'crm_property_group_id' => $group->id]);

        $this->post(route('crm.properties.store'), [
            'crm_property_group_id' => $group->id,
            'name' => 'Notiz',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ])->assertSessionHasErrors('name');

        // Altbestand mit doppeltem Namen: andere Felder bleiben änderbar
        $this->patch(route('crm.properties.update', $twin), [
            'name' => 'Notiz',
            'tooltip_text' => 'Hinweis',
        ])->assertSessionHasNoErrors();

        $other = CrmProperty::factory()->create(['name' => 'Anders', 'crm_property_group_id' => $group->id]);
        $this->patch(route('crm.properties.update', $other), ['name' => 'Notiz'])->assertSessionHasErrors('name');
        $this->assertSame('Notiz', $first->refresh()->name);
    }

    /**
     * @return array<string, array{CrmPropertyTypeEnum, string, string|false}>
     */
    public static function importValues(): array
    {
        return [
            'Dezimalkomma' => [CrmPropertyTypeEnum::NUMBER, '12,5', '12.5'],
            'Tausenderpunkt' => [CrmPropertyTypeEnum::NUMBER, '1.234,56', '1234.56'],
            'Punkt bleibt' => [CrmPropertyTypeEnum::NUMBER, '12.5', '12.5'],
            'keine Zahl' => [CrmPropertyTypeEnum::NUMBER, 'zwölf', false],
            'Checkbox ja' => [CrmPropertyTypeEnum::CHECKBOX, 'Ja', '1'],
            'Checkbox wahr' => [CrmPropertyTypeEnum::CHECKBOX, 'wahr', '1'],
            'Checkbox nein' => [CrmPropertyTypeEnum::CHECKBOX, 'Nein', '0'],
            'Checkbox Tippfehler' => [CrmPropertyTypeEnum::CHECKBOX, 'jaa', false],
        ];
    }

    #[Test]
    #[DataProvider('importValues')]
    public function import_casts_numbers_and_checkboxes(
        CrmPropertyTypeEnum $type,
        string $raw,
        string|false $expected
    ): void {
        $property = CrmProperty::factory()->make(['type' => $type->value]);
        $castValue = new ReflectionMethod(CrmImportService::class, 'castValue');

        $this->assertSame($expected, $castValue->invoke(app(CrmImportService::class), $raw, $property));
    }

    #[Test]
    public function import_matches_select_options_stored_as_numbers(): void
    {
        $property = CrmProperty::factory()->make([
            'type' => CrmPropertyTypeEnum::SELECT->value,
            'select_values' => [1, 2],
        ]);
        $castValue = new ReflectionMethod(CrmImportService::class, 'castValue');

        $this->assertSame('2', $castValue->invoke(app(CrmImportService::class), '2', $property));
    }
}
