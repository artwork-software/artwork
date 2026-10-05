<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ApprovalOfContactFieldsTest extends TestCase
{
    #[Test]
    public function standalone_contact_display_name_and_property_are_applied_only_after_approval(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency']);
        $group = CrmPropertyGroup::query()->create(['name' => 'Kontakt', 'is_confidential' => false]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Telefon',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => false]);
        $contact = CrmContact::query()->create(['crm_contact_type_id' => $type->id, 'display_name' => 'Alt', 'is_active' => true]);
        $external = ExternalAccess::factory()->active()->create(['crm_contact_id' => $contact->id, 'invited_by_user_id' => $inviter->id]);

        $submission = app(ExternalSelfEditSubmissionService::class)->submit($external, [
            'personal' => ['display_name' => 'Neu GmbH'],
            'crm_group_' . $group->id => ['crm_property:' . $property->id => '+49 30 123'],
        ]);

        $this->assertNotNull($submission);
        $this->assertSame(2, $submission->fieldChanges()->count());
        $this->assertSame('Alt', $contact->fresh()->display_name);
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id, 'crm_property_id' => $property->id]);

        app(ExternalSubmissionApprovalService::class)->approveAll($submission, $inviter);

        $this->assertSame(ExternalSubmissionStatus::APPROVED, $submission->fresh()->status);
        $this->assertSame('Neu GmbH', $contact->fresh()->display_name);
        $this->assertDatabaseHas('crm_property_values', ['crm_contact_id' => $contact->id, 'crm_property_id' => $property->id, 'value' => '+49 30 123']);
    }
}
