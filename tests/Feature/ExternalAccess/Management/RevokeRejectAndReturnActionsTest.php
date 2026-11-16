<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Funktionstests für Sperren/Reaktivieren externer Zugänge, das Ablehnen einer kompletten
 * Selbstauskunft und das Zurückgeben eines abgesendeten Tabs.
 */
final class RevokeRejectAndReturnActionsTest extends TestCase
{
    use CrmContactListFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function the_inviter_can_revoke_and_reactivate_an_access(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id, 'revoked_at' => null]);

        $this->from('/access')
            ->post(route('crm.external-access.revoke', $access->id), ['reason' => 'Gastspiel abgesagt'])
            ->assertRedirect('/access')
            ->assertSessionHas('status');
        $this->assertNotNull($access->fresh()->revoked_at);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $access->id,
            'description' => 'access_revoked',
        ]);

        $this->post(route('crm.external-access.reactivate', $access->id))->assertRedirect()->assertSessionHas('status');
        $this->assertNull($access->fresh()->revoked_at);
    }

    #[Test]
    public function revoking_twice_keeps_the_first_revocation_time(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $revokedAt = now()->subDays(3)->startOfSecond();
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id, 'revoked_at' => $revokedAt]);

        $this->post(route('crm.external-access.revoke', $access->id))->assertRedirect();

        $this->assertEquals($revokedAt, $access->fresh()->revoked_at);
    }

    #[Test]
    public function reactivating_an_active_access_changes_nothing(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id, 'revoked_at' => null]);

        $this->post(route('crm.external-access.reactivate', $access->id))->assertRedirect();

        $this->assertNull($access->fresh()->revoked_at);
        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $access->id,
            'description' => 'access_reactivated',
        ]);
    }

    #[Test]
    public function the_revocation_reason_is_limited(): void
    {
        $inviter = $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $access = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id, 'revoked_at' => null]);

        $this->postJson(route('crm.external-access.revoke', $access->id), ['reason' => str_repeat('x', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertNull($access->fresh()->revoked_at);
    }

    /**
     * @return array{0: ExternalPendingSubmission, 1: Freelancer, 2: ExternalAccess, 3: ExternalPendingFieldChange}
     */
    private function pendingSubmission(User $inviter): array
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $freelancer = Freelancer::factory()->create(['zip_code' => '20095']);
        $freelancer->createCrmContact();
        $access = ExternalAccess::factory()->create([
            'crm_contact_id' => $freelancer->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);
        $submission = ExternalPendingSubmission::query()->create([
            'external_access_id' => $access->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        $change = ExternalPendingFieldChange::query()->create([
            'submission_id' => $submission->id,
            'target_type' => $freelancer->getMorphClass(),
            'target_id' => $freelancer->id,
            'field_key' => 'zip_code',
            'old_value' => '20095',
            'new_value' => '10117',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        return [$submission, $freelancer, $access, $change];
    }

    #[Test]
    public function rejecting_a_submission_keeps_the_data_and_stores_the_reason(): void
    {
        $inviter = User::factory()->create();
        [$submission, $freelancer, $access, $change] = $this->pendingSubmission($inviter);
        $this->actingAs($inviter);

        $this->post(
            route('crm.contacts.external-submissions.reject-all', [$access->crm_contact_id, $submission->id]),
            ['reason' => 'Bitte korrekte Postleitzahl']
        )->assertRedirect(route('crm.contacts.external-submissions.show', [$access->crm_contact_id, $submission->id]))
            ->assertSessionHas('status');

        $submission->refresh();
        $this->assertSame(ExternalSubmissionStatus::REJECTED, $submission->status);
        $this->assertSame('Bitte korrekte Postleitzahl', $submission->rejection_reason);
        $this->assertSame($inviter->id, $submission->reviewed_by_user_id);
        $this->assertSame(FieldApprovalStatus::REJECTED, $change->fresh()->approval_status);
        $this->assertSame('20095', $freelancer->fresh()->zip_code);
    }

    #[Test]
    public function a_decided_submission_cannot_be_rejected_again(): void
    {
        $inviter = User::factory()->create();
        [$submission, $freelancer, $access] = $this->pendingSubmission($inviter);
        $this->actingAs($inviter);
        $this->post(route('crm.contacts.external-submissions.approve-all', [$access->crm_contact_id, $submission->id]))
            ->assertRedirect();

        $this->post(route('crm.contacts.external-submissions.reject-all', [$access->crm_contact_id, $submission->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(ExternalSubmissionStatus::APPROVED, $submission->fresh()->status);
        $this->assertSame('10117', $freelancer->fresh()->zip_code);
    }

    #[Test]
    public function rejecting_validates_the_reason_and_the_contact(): void
    {
        $inviter = User::factory()->create();
        [$submission, , $access] = $this->pendingSubmission($inviter);
        [, , $otherAccess] = $this->pendingSubmission($inviter);
        $this->actingAs($inviter);

        $this->postJson(
            route('crm.contacts.external-submissions.reject-all', [$access->crm_contact_id, $submission->id]),
            ['reason' => str_repeat('x', 2001)]
        )->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->post(route('crm.contacts.external-submissions.reject-all', [$otherAccess->crm_contact_id, $submission->id]))
            ->assertNotFound();

        $this->assertSame(ExternalSubmissionStatus::PENDING, $submission->fresh()->status);
    }

    private function reviewRoute(array $context): string
    {
        return route('projects.tabs.externals.return', [
            'project' => $context['project']->id,
            'projectTab' => $context['tab']->id,
            'scope' => $context['scope']->id,
        ]);
    }

    #[Test]
    public function a_confirmed_tab_can_be_returned_and_a_blank_comment_is_dropped(): void
    {
        $context = $this->crmContactListContext();
        $context['scope']->forceFill(['submission_status' => ExternalTabSubmissionStatus::CONFIRMED])->save();
        $this->actingAs($context['inviter']);

        $this->postJson($this->reviewRoute($context), ['comment' => '   '])
            ->assertOk()
            ->assertJsonPath('external.status', 'returned')
            ->assertJsonPath('external.review_comment', null);

        $scope = $context['scope']->fresh();
        $this->assertSame(ExternalTabSubmissionStatus::RETURNED, $scope->submission_status);
        $this->assertSame($context['inviter']->id, $scope->reviewed_by_user_id);
    }

    #[Test]
    public function a_tab_that_was_not_submitted_cannot_be_returned(): void
    {
        $context = $this->crmContactListContext();
        $this->actingAs($context['inviter']);

        $this->postJson($this->reviewRoute($context), ['comment' => 'zu früh'])->assertUnprocessable();

        $this->assertNotSame(ExternalTabSubmissionStatus::RETURNED, $context['scope']->fresh()->submission_status);
    }

    #[Test]
    public function the_return_comment_is_limited_and_foreign_scopes_are_not_found(): void
    {
        $context = $this->crmContactListContext();
        $context['scope']->forceFill(['submission_status' => ExternalTabSubmissionStatus::SUBMITTED])->save();
        $other = $this->crmContactListContext();
        $this->actingAs($context['inviter']);

        $this->postJson($this->reviewRoute($context), ['comment' => str_repeat('x', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        $this->postJson(route('projects.tabs.externals.return', [
            'project' => $context['project']->id,
            'projectTab' => $context['tab']->id,
            'scope' => $other['scope']->id,
        ]))->assertNotFound();

        $this->assertSame(ExternalTabSubmissionStatus::SUBMITTED, $context['scope']->fresh()->submission_status);
    }
}
