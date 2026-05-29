<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Freelancer\Models\Freelancer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StagingModelTest extends TestCase
{
    private function submission(ExternalAccess $external, ExternalSubmissionStatus $status): ExternalPendingSubmission
    {
        return ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    #[Test]
    public function current_pending_scope_returns_only_pending_for_the_external(): void
    {
        $external = ExternalAccess::factory()->create();
        $other = ExternalAccess::factory()->create();

        $pending = $this->submission($external, ExternalSubmissionStatus::PENDING);
        $this->submission($external, ExternalSubmissionStatus::SUPERSEDED);
        $this->submission($other, ExternalSubmissionStatus::PENDING);

        $result = ExternalPendingSubmission::currentPending($external)->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($pending));
    }

    #[Test]
    public function field_change_morphs_to_freelancer(): void
    {
        $external = ExternalAccess::factory()->create();
        $submission = $this->submission($external, ExternalSubmissionStatus::PENDING);
        $freelancer = Freelancer::factory()->create();

        $change = ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $freelancer->getMorphClass(),
            'target_id' => $freelancer->id,
            'field_key' => 'zip_code',
            'old_value' => '10115',
            'new_value' => '10117',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        $this->assertTrue($change->target->is($freelancer));
        $this->assertSame('10117', $change->new_value);
    }

    #[Test]
    public function field_change_morphs_to_crm_contact(): void
    {
        $external = ExternalAccess::factory()->create();
        $submission = $this->submission($external, ExternalSubmissionStatus::PENDING);
        $contact = $external->crmContact;

        $change = ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $contact->getMorphClass(),
            'target_id' => $contact->id,
            'field_key' => 'crm_property:1',
            'old_value' => null,
            'new_value' => 'value',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        $this->assertInstanceOf(CrmContact::class, $change->target);
        $this->assertTrue($change->target->is($contact));
    }

    #[Test]
    public function submission_has_field_changes_relation(): void
    {
        $external = ExternalAccess::factory()->create();
        $submission = $this->submission($external, ExternalSubmissionStatus::PENDING);
        $freelancer = Freelancer::factory()->create();

        ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $freelancer->getMorphClass(),
            'target_id' => $freelancer->id,
            'field_key' => 'first_name',
            'old_value' => 'A',
            'new_value' => 'B',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        $this->assertSame(1, $submission->fieldChanges()->count());
        $this->assertTrue($submission->externalAccess->is($external));
    }
}
