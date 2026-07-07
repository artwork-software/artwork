<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Notifications\ExternalCrmSubmissionNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalReviewResultNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationSender;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TriggerIntegrationTest extends TestCase
{
    /**
     * @return array{0:ExternalAccess,1:Freelancer,2:User}
     */
    private function freelancerExternal(): array
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $inviter = User::factory()->create();
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'zip_code' => '10115']);
        $fl->createCrmContact();
        $external = ExternalAccess::factory()->create([
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        return [$external, $fl, $inviter];
    }

    #[Test]
    public function staged_crm_submission_notifies_inviter(): void
    {
        Notification::fake();
        [$external, , $inviter] = $this->freelancerExternal();

        app(ExternalSelfEditSubmissionService::class)->submit($external, ['personal' => ['zip_code' => '10117']]);

        Notification::assertSentTo($inviter, ExternalCrmSubmissionNotification::class);
    }

    #[Test]
    public function direct_only_crm_change_notifies_inviter(): void
    {
        Notification::fake();
        [$external, , $inviter] = $this->freelancerExternal();

        $group = CrmPropertyGroup::query()->create(['name' => 'Allgemein', 'is_confidential' => false]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Lieblingsfarbe',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach(
            CrmContactType::query()->where('slug', 'freelancer')->value('id'),
            ['is_required' => false],
        );

        app(ExternalSelfEditSubmissionService::class)->submit($external, [
            'crm_group_' . $group->id => ['crm_property:' . $property->id => 'Blau'],
        ]);

        Notification::assertSentTo($inviter, ExternalCrmSubmissionNotification::class);
    }

    #[Test]
    public function approval_notifies_external_user_via_mail(): void
    {
        Notification::fake();
        [$external, $fl, $inviter] = $this->freelancerExternal();

        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $fl->getMorphClass(),
            'target_id' => $fl->id,
            'field_key' => 'zip_code',
            'old_value' => null,
            'new_value' => '10117',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        app(ExternalSubmissionApprovalService::class)->approveAll($submission, $inviter);

        Notification::assertSentTo($external, ExternalReviewResultNotification::class);
    }

    #[Test]
    public function notification_failure_does_not_rollback_approval(): void
    {
        [$external, $fl, $inviter] = $this->freelancerExternal();

        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $fl->getMorphClass(),
            'target_id' => $fl->id,
            'field_key' => 'zip_code',
            'old_value' => null,
            'new_value' => '10117',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        // Sender throws AFTER the transaction committed — approval must persist.
        $this->mock(ExternalNotificationSender::class, function ($mock): void {
            $mock->shouldReceive('notifyExternalReviewResult')->andThrow(new \RuntimeException('mail down'));
        });

        try {
            app(ExternalSubmissionApprovalService::class)->approveAll($submission, $inviter);
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(ExternalSubmissionStatus::APPROVED, $submission->fresh()->status);
        $this->assertSame('10117', $fl->fresh()->zip_code);
    }
}
