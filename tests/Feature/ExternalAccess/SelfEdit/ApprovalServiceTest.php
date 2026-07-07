<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Exceptions\EmailAlreadyInUseException;
use Artwork\Modules\ExternalAccess\Exceptions\NotAuthorizedToReviewException;
use Artwork\Modules\ExternalAccess\Exceptions\SubmissionAlreadyDecidedException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ApprovalServiceTest extends TestCase
{
    private function service(): ExternalSubmissionApprovalService
    {
        return app(ExternalSubmissionApprovalService::class);
    }

    /**
     * @param list<array{field_key:string,old:mixed,new:mixed}> $changes
     * @return array{0:ExternalPendingSubmission,1:Freelancer,2:ExternalAccess}
     */
    private function pending(User $inviter, array $changes): array
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test']);
        $fl->createCrmContact();
        $external = ExternalAccess::factory()->create([
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);

        foreach ($changes as $c) {
            ExternalPendingFieldChange::create([
                'submission_id' => $submission->id,
                'target_type' => $fl->getMorphClass(),
                'target_id' => $fl->id,
                'field_key' => $c['field_key'],
                'old_value' => $c['old'],
                'new_value' => $c['new'],
                'approval_status' => FieldApprovalStatus::PENDING,
            ]);
        }

        return [$submission, $fl, $external];
    }

    #[Test]
    public function approve_all_writes_fields_and_syncs_crm(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl] = $this->pending($inviter, [
            ['field_key' => 'first_name', 'old' => 'Ada', 'new' => 'Augusta'],
            ['field_key' => 'zip_code', 'old' => null, 'new' => '10117'],
        ]);

        $this->service()->approveAll($submission, $inviter);

        $this->assertSame('Augusta', $fl->fresh()->first_name);
        $this->assertSame('10117', $fl->fresh()->zip_code);
        $this->assertSame(ExternalSubmissionStatus::APPROVED, $submission->fresh()->status);
        // syncToCrm updated the display name
        $this->assertSame('Augusta Lovelace', $fl->crmContact()->first()->display_name);
    }

    #[Test]
    public function reject_all_writes_no_fields(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl] = $this->pending($inviter, [
            ['field_key' => 'first_name', 'old' => 'Ada', 'new' => 'Augusta'],
        ]);

        $this->service()->rejectAll($submission, $inviter, 'Nope');

        $this->assertSame('Ada', $fl->fresh()->first_name);
        $this->assertSame(ExternalSubmissionStatus::REJECTED, $submission->fresh()->status);
        $this->assertSame('Nope', $submission->fresh()->rejection_reason);
    }

    #[Test]
    public function partial_decisions_set_partially_approved(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl] = $this->pending($inviter, [
            ['field_key' => 'first_name', 'old' => 'Ada', 'new' => 'Augusta'],
            ['field_key' => 'zip_code', 'old' => null, 'new' => '10117'],
        ]);
        $changes = $submission->fieldChanges()->orderBy('id')->get();

        $this->service()->applyPartialDecisions($submission, $inviter, [
            $changes[0]->id => FieldApprovalStatus::APPROVED,
            $changes[1]->id => FieldApprovalStatus::REJECTED,
        ]);

        $this->assertSame('Augusta', $fl->fresh()->first_name);
        $this->assertNull($fl->fresh()->zip_code);
        $this->assertSame(ExternalSubmissionStatus::PARTIALLY_APPROVED, $submission->fresh()->status);
    }

    #[Test]
    public function non_whitelisted_field_throws(): void
    {
        $inviter = User::factory()->create();
        [$submission] = $this->pending($inviter, [
            ['field_key' => 'salary_per_hour', 'old' => null, 'new' => '999'],
        ]);

        $this->expectException(\DomainException::class);
        $this->service()->approveAll($submission, $inviter);
    }

    #[Test]
    public function confidential_property_apply_throws(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl, $external] = $this->pending($inviter, []);

        $group = CrmPropertyGroup::query()->create(['name' => 'Vertraulich', 'is_confidential' => true]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id, 'name' => 'IBAN', 'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $external->crmContact->getMorphClass(),
            'target_id' => $external->crm_contact_id,
            'field_key' => 'crm_property:' . $property->id,
            'old_value' => null, 'new_value' => 'DE00', 'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        $this->expectException(\DomainException::class);
        $this->service()->approveAll($submission, $inviter);
    }

    #[Test]
    public function non_inviter_non_admin_cannot_review(): void
    {
        $inviter = User::factory()->create();
        [$submission] = $this->pending($inviter, [['field_key' => 'zip_code', 'old' => null, 'new' => '1']]);
        $stranger = User::factory()->create();

        $this->expectException(NotAuthorizedToReviewException::class);
        $this->service()->approveAll($submission, $stranger);
    }

    #[Test]
    public function admin_can_review_any_submission(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl] = $this->pending($inviter, [['field_key' => 'zip_code', 'old' => null, 'new' => '10117']]);
        $admin = $this->adminUser();

        $this->service()->approveAll($submission, $admin);

        $this->assertSame('10117', $fl->fresh()->zip_code);
    }

    #[Test]
    public function already_decided_submission_throws_on_second_review(): void
    {
        $inviter = User::factory()->create();
        [$submission] = $this->pending($inviter, [['field_key' => 'zip_code', 'old' => null, 'new' => '10117']]);

        $this->service()->approveAll($submission, $inviter);

        $this->expectException(SubmissionAlreadyDecidedException::class);
        $this->service()->approveAll($submission->fresh(), $inviter);
    }

    #[Test]
    public function approving_email_change_updates_external_access_email(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl, $external] = $this->pending($inviter, [
            ['field_key' => 'email', 'old' => 'ada@example.test', 'new' => 'new@example.test'],
        ]);

        $this->service()->approveAll($submission, $inviter);

        $this->assertSame('new@example.test', $fl->fresh()->email);
        $this->assertSame('new@example.test', $external->fresh()->email);
    }

    #[Test]
    public function approving_phone_change_does_not_touch_external_access_email(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl, $external] = $this->pending($inviter, [
            ['field_key' => 'phone_number', 'old' => null, 'new' => '+49 30 1234'],
        ]);
        $originalEmail = $external->email;

        $this->service()->approveAll($submission, $inviter);

        $this->assertSame($originalEmail, $external->fresh()->email);
    }

    #[Test]
    public function approving_email_change_to_existing_external_email_throws(): void
    {
        $inviter = User::factory()->create();
        ExternalAccess::factory()->create(['email' => 'taken@example.test']);
        [$submission] = $this->pending($inviter, [
            ['field_key' => 'email', 'old' => 'ada@example.test', 'new' => 'taken@example.test'],
        ]);

        $this->expectException(EmailAlreadyInUseException::class);
        $this->service()->approveAll($submission, $inviter);
    }
}
