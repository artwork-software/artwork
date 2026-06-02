<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SubmissionServiceTest extends TestCase
{
    private function service(): ExternalSelfEditSubmissionService
    {
        return app(ExternalSelfEditSubmissionService::class);
    }

    private function freelancerExternal(array $attrs = []): array
    {
        $type = CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $freelancer = Freelancer::factory()->create(array_merge([
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test', 'zip_code' => '10115',
        ], $attrs));
        $freelancer->createCrmContact();
        $external = ExternalAccess::factory()->create(['crm_contact_id' => $freelancer->crmContact()->firstOrFail()->id]);

        return [$external, $freelancer, $type];
    }

    private function attachDirectProperty(CrmContactType $type): CrmProperty
    {
        $group = CrmPropertyGroup::query()->create(['name' => 'Allgemein', 'is_confidential' => false]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Lieblingsfarbe',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => false]);

        return $property;
    }

    #[Test]
    public function staged_field_change_creates_pending_submission(): void
    {
        [$external] = $this->freelancerExternal();

        $submission = $this->service()->submit($external, [
            'personal' => ['zip_code' => '10117'],
        ]);

        $this->assertNotNull($submission);
        $this->assertSame(ExternalSubmissionStatus::PENDING, $submission->status);
        $this->assertSame(1, $submission->fieldChanges()->count());
        $this->assertSame('10117', $submission->fieldChanges()->first()->new_value);
    }

    #[Test]
    public function direct_crm_property_change_writes_immediately(): void
    {
        [$external, , $type] = $this->freelancerExternal();
        $property = $this->attachDirectProperty($type);

        $result = $this->service()->submit($external, [
            'crm_group_' . $property->crm_property_group_id => ['crm_property:' . $property->id => 'Blau'],
        ]);

        $this->assertNull($result); // only direct writes
        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $property->id,
            'value' => 'Blau',
        ]);
    }

    #[Test]
    public function unchanged_fields_are_not_persisted(): void
    {
        [$external] = $this->freelancerExternal();

        $submission = $this->service()->submit($external, [
            'personal' => ['zip_code' => '10115', 'first_name' => 'Ada'], // same as current
        ]);

        $this->assertNull($submission);
        $this->assertSame(0, ExternalPendingSubmission::query()->where('external_access_id', $external->id)->count());
    }

    #[Test]
    public function previous_pending_is_superseded_on_new_submission(): void
    {
        [$external] = $this->freelancerExternal();

        $first = $this->service()->submit($external, ['personal' => ['zip_code' => '10117']]);
        $second = $this->service()->submit($external, ['personal' => ['zip_code' => '10119']]);

        $this->assertSame(ExternalSubmissionStatus::SUPERSEDED, $first->fresh()->status);
        $this->assertSame(ExternalSubmissionStatus::PENDING, $second->fresh()->status);
    }

    #[Test]
    public function missing_required_field_throws_validation_exception(): void
    {
        [$external] = $this->freelancerExternal();

        $this->expectException(ValidationException::class);

        $this->service()->submit($external, ['personal' => ['first_name' => '']]);
    }

    #[Test]
    public function submission_with_only_direct_writes_returns_null(): void
    {
        [$external, , $type] = $this->freelancerExternal();
        $property = $this->attachDirectProperty($type);

        $result = $this->service()->submit($external, [
            'crm_group_' . $property->crm_property_group_id => ['crm_property:' . $property->id => 'Grün'],
        ]);

        $this->assertNull($result);
    }
}
