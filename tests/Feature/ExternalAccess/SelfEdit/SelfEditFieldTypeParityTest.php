<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditField;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditFieldResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Externe CRM-Selbstbearbeitung muss je Eigenschaftstyp dieselbe Eingabe wie intern
 * (CrmPropertyValueInput) zeigen und speichern — kein Freitextfeld, wo intern ein Dropdown steht.
 */
final class SelfEditFieldTypeParityTest extends TestCase
{
    private CrmContactType $type;

    private CrmPropertyGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency']);
        $this->group = CrmPropertyGroup::query()->create(['name' => 'Allgemein', 'is_confidential' => false]);
    }

    /**
     * @param array<int, string>|null $selectValues
     */
    private function property(CrmPropertyTypeEnum $type, ?array $selectValues = null): CrmProperty
    {
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $this->group->id,
            'name' => 'Feld ' . $type->value,
            'type' => $type->value,
            'select_values' => $selectValues,
        ]);
        $property->contactTypes()->attach($this->type->id, ['is_required' => false]);

        return $property;
    }

    private function external(): ExternalAccess
    {
        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $this->type->id,
            'display_name' => 'Agentur Nord',
            'is_active' => true,
        ]);

        return ExternalAccess::factory()->active()->create([
            'crm_contact_id' => $contact->id,
            'invited_by_user_id' => User::factory()->create()->id,
        ]);
    }

    private function fieldFor(ExternalAccess $external, CrmProperty $property): ?SelfEditField
    {
        $schema = app(ExternalSelfEditFieldResolver::class)->resolveFor($external);

        foreach ($schema->sections as $section) {
            foreach ($section->fields as $field) {
                if ($field->key === 'crm_property:' . $property->id) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function submit(ExternalAccess $external, array $values): ?ExternalPendingSubmission
    {
        return app(ExternalSelfEditSubmissionService::class)->submit($external, [
            'crm_group_' . $this->group->id => $values,
        ]);
    }

    #[Test]
    public function every_crm_property_type_has_an_explicit_external_input_type(): void
    {
        foreach (CrmPropertyTypeEnum::cases() as $case) {
            $inputType = ExternalSelfEditFieldResolver::inputTypeFor($case);

            if ($case === CrmPropertyTypeEnum::UPLOAD) {
                // Datei-Feld: die Datei geht vorläufig mit der Einreichung mit, nie als Text-Pfad
                $this->assertSame('file', $inputType);
                continue;
            }

            $this->assertNotNull($inputType, $case->value);
            // Nur der echte Texttyp darf als Freitextfeld erscheinen
            $this->assertSame($case === CrmPropertyTypeEnum::TEXT, $inputType === 'text', $case->value);
        }
    }

    #[Test]
    public function select_property_is_offered_as_dropdown_with_its_options(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::SELECT, ['Klein', '', 'Groß']);

        $field = $this->fieldFor($this->external(), $property);

        $this->assertNotNull($field);
        $this->assertSame('select', $field->inputType);
        $this->assertSame(['Klein', 'Groß'], $field->options);
        $this->assertSame(['Klein', 'Groß'], $field->toArray()['options']);
    }

    #[Test]
    public function upload_property_is_not_offered_as_text_field(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::UPLOAD);
        $external = $this->external();
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $property->id,
            'value' => 'crm-property-files/0123456789abcdef0123456789abcdef.pdf',
        ]);

        $field = $this->fieldFor($external, $property);

        $this->assertNotNull($field);
        $this->assertSame('file', $field->inputType);
        // nur der Dateiname, nie der Speicherpfad
        $this->assertSame('0123456789abcdef0123456789abcdef.pdf', $field->value);
    }

    #[Test]
    public function select_value_outside_the_options_is_rejected(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::SELECT, ['Klein', 'Groß']);

        $this->expectException(ValidationException::class);

        $this->submit($this->external(), ['crm_property:' . $property->id => 'Freitext']);
    }

    #[Test]
    public function select_value_from_the_options_is_staged(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::SELECT, ['Klein', 'Groß']);

        $submission = $this->submit($this->external(), ['crm_property:' . $property->id => 'Groß']);

        $this->assertNotNull($submission);
        $this->assertSame('Groß', $submission->fieldChanges()->first()->new_value);
    }

    #[Test]
    public function unchanged_select_value_no_longer_in_options_does_not_block_submit(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::SELECT, ['Klein', 'Groß']);
        $external = $this->external();
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $property->id,
            'value' => 'Mittel',
        ]);

        $submission = $this->submit($external, ['crm_property:' . $property->id => 'Mittel']);

        $this->assertNull($submission);
    }

    #[Test]
    public function checkbox_is_stored_as_one_or_zero_like_internally(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::CHECKBOX);
        $external = $this->external();

        // Nie gesetzt + nicht angehakt = keine Änderung
        $this->assertNull($this->submit($external, ['crm_property:' . $property->id => '0']));

        $submission = $this->submit($external, ['crm_property:' . $property->id => true]);

        $this->assertNotNull($submission);
        $this->assertSame('1', $submission->fieldChanges()->first()->new_value);
    }

    #[Test]
    public function invalid_number_and_date_are_rejected(): void
    {
        $number = $this->property(CrmPropertyTypeEnum::NUMBER);
        $date = $this->property(CrmPropertyTypeEnum::DATE);
        $external = $this->external();

        foreach ([$number->id => 'zwölf', $date->id => '31.12.2026'] as $propertyId => $value) {
            try {
                $this->submit($external, ['crm_property:' . $propertyId => $value]);
                $this->fail('Expected validation error for property ' . $propertyId);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(
                    'values.crm_group_' . $this->group->id . '.crm_property:' . $propertyId,
                    $exception->errors(),
                );
            }
        }
    }

    #[Test]
    public function legacy_upload_change_is_never_applied(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::UPLOAD);
        $external = $this->external();
        $submission = ExternalPendingSubmission::query()->create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        ExternalPendingFieldChange::query()->create([
            'submission_id' => $submission->id,
            'target_type' => (new CrmContact())->getMorphClass(),
            'target_id' => $external->crm_contact_id,
            'field_key' => 'crm_property:' . $property->id,
            'old_value' => null,
            'new_value' => 'crm-property-files/fremdedatei.pdf',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        // Altbestand wird übersprungen statt die ganze Freigabe abzubrechen
        $skipped = app(ExternalSubmissionApprovalService::class)->approveAll($submission, $external->invitedBy);

        $this->assertSame(1, $skipped);
        $this->assertDatabaseMissing('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $property->id,
        ]);
        $this->assertSame(FieldApprovalStatus::REJECTED, $submission->fieldChanges()->first()->approval_status);
        $this->assertSame(ExternalSubmissionStatus::REJECTED, $submission->fresh()->status);
    }

    #[Test]
    public function link_with_executable_target_is_rejected(): void
    {
        $property = $this->property(CrmPropertyTypeEnum::LINK);

        $this->expectException(ValidationException::class);

        $this->submit($this->external(), ['crm_property:' . $property->id => 'javascript:alert(1)']);
    }

    #[Test]
    public function groups_and_properties_follow_the_contact_type_order_like_internally(): void
    {
        $groups = [];
        foreach ([2, 10] as $suffix) {
            $groups[$suffix] = CrmPropertyGroup::query()->create(['name' => 'Gruppe ' . $suffix, 'is_confidential' => false]);
        }
        // Maßgeblich ist die Sortierung im Kontakttyp, nicht die Anlage-Reihenfolge der Gruppen
        $create = function (CrmPropertyGroup $group, string $name, int $typeOrder): void {
            $property = CrmProperty::query()->create([
                'crm_property_group_id' => $group->id,
                'name' => $name,
                'type' => CrmPropertyTypeEnum::TEXT->value,
            ]);
            $property->contactTypes()->attach($this->type->id, ['is_required' => false, 'sort_order' => $typeOrder]);
        };
        $create($groups[10], 'Zuerst B', 3);
        $create($groups[10], 'Zuerst A', 1);
        $create($groups[2], 'Danach', 5);
        $create($this->group, 'Zuletzt', 9);

        $schema = app(ExternalSelfEditFieldResolver::class)->resolveFor($this->external());
        $propertySections = array_values(array_filter(
            $schema->sections,
            static fn ($section) => str_starts_with($section->key, 'crm_group_'),
        ));

        $this->assertSame(
            ['Gruppe 10', 'Gruppe 2', 'Allgemein'],
            array_map(static fn ($section) => $section->label, $propertySections),
        );
        $this->assertSame(
            ['Zuerst A', 'Zuerst B'],
            array_map(static fn ($field) => $field->label, $propertySections[0]->fields),
        );
    }
}
