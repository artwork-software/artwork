<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Rules\SafeCrmLinkValue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Link-Eigenschaften im CRM: ausführbare Schemata (javascript:, data:, vbscript:) werden auf jedem
 * Schreibweg abgewiesen (Formular, Import, Aufenthalte, Einladung). Schemalose Werte bleiben erlaubt.
 */
final class CrmLinkPropertyValidationTest extends FeatureTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function dangerousLinks(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'mixed case with whitespace' => ['  JavaScript:alert(1)'],
            'control characters inside scheme' => ["java\tscript:alert(1)"],
            'data' => ['data:text/html;base64,PHNjcmlwdD4='],
            'vbscript' => ['vbscript:msgbox(1)'],
        ];
    }

    #[Test]
    #[DataProvider('dangerousLinks')]
    public function update_rejects_executable_link_schemes(string $link): void
    {
        $this->actingAsAdmin();
        $property = $this->linkProperty();
        $contact = CrmContact::factory()->create();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => $link],
        ])->assertSessionHasErrors('property_values.' . $property->id);

        $this->assertNull($this->storedValue($contact, $property));
    }

    #[Test]
    public function update_accepts_http_and_schemeless_links(): void
    {
        $this->actingAsAdmin();
        $property = $this->linkProperty();
        $contact = CrmContact::factory()->create();

        foreach (['https://example.org/profil', 'http://example.org', 'www.example.org'] as $link) {
            $this->patch(route('crm.contacts.update', $contact), [
                'property_values' => [$property->id => $link],
            ])->assertSessionHasNoErrors();

            $this->assertSame($link, $this->storedValue($contact, $property));
        }
    }

    #[Test]
    public function text_properties_are_not_checked_as_links(): void
    {
        $this->actingAsAdmin();
        $property = CrmProperty::factory()->create(['type' => CrmPropertyTypeEnum::TEXT->value]);
        $contact = CrmContact::factory()->create();

        $this->patch(route('crm.contacts.update', $contact), [
            'property_values' => [$property->id => 'javascript: ist hier nur Text'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('javascript: ist hier nur Text', $this->storedValue($contact, $property));
    }

    #[Test]
    public function store_rejects_executable_link_schemes(): void
    {
        $this->actingAsAdmin();
        $property = $this->linkProperty();
        $type = CrmContactType::factory()->create();

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Link-Test',
            'property_values' => [$property->id => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('property_values.' . $property->id);

        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Link-Test']);
    }

    #[Test]
    public function rule_checks_property_keyed_lists_of_other_forms(): void
    {
        $property = $this->linkProperty();
        $rules = ['crm_property_values.*' => ['nullable', 'string', new SafeCrmLinkValue()]];

        $unsafe = Validator::make(['crm_property_values' => [$property->id => 'javascript:alert(1)']], $rules);
        $this->assertTrue($unsafe->fails());
        $this->assertTrue($unsafe->errors()->has('crm_property_values.' . $property->id));

        $safe = Validator::make(['crm_property_values' => [$property->id => 'https://example.org']], $rules);
        $this->assertFalse($safe->fails());

        // Nicht-numerische Schlüssel (Stammdaten-Felder) und unbekannte IDs werden übergangen
        $other = Validator::make(
            ['crm_property_values' => ['name' => 'javascript:x', 999999 => 'javascript:x']],
            $rules,
        );
        $this->assertFalse($other->fails());
    }

    #[Test]
    public function import_skips_rows_with_executable_link_schemes(): void
    {
        $this->actingAsAdmin();
        $type = CrmContactType::factory()->create(['is_system' => false, 'is_active' => true]);
        $property = $this->linkProperty();
        $type->properties()->attach($property->id, ['sort_order' => 0]);

        $csv = "Name,Webseite\n"
            . "Sichere Person,https://example.org\n"
            . "Unsichere Person,javascript:alert(1)\n";

        $this->post(route('crm.import.upload'), [
            'file' => UploadedFile::fake()->createWithContent('kontakte.csv', $csv),
            'use_type_column' => '0',
            'crm_contact_type_id' => $type->id,
        ])->assertOk();

        $this->post(route('crm.import.execute'), [
            'mapping' => [
                'display_name' => 0,
                'properties' => [(string) $property->id => 1],
            ],
            'duplicates' => null,
        ])->assertRedirect();

        $result = session('importResult');
        $this->assertSame(1, $result['created']);
        $this->assertCount(1, $result['skipped']);
        $this->assertDatabaseHas('crm_contacts', ['display_name' => 'Sichere Person']);
        $this->assertDatabaseMissing('crm_contacts', ['display_name' => 'Unsichere Person']);
        $this->assertDatabaseMissing('crm_property_values', ['value' => 'javascript:alert(1)']);
    }

    private function linkProperty(): CrmProperty
    {
        return CrmProperty::factory()->create([
            'name' => 'Webseite',
            'type' => CrmPropertyTypeEnum::LINK->value,
        ]);
    }

    private function storedValue(CrmContact $contact, CrmProperty $property): ?string
    {
        return CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $property->id)
            ->value('value');
    }
}
