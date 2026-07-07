<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReviewControllerTest extends TestCase
{
    /**
     * @return array{0:ExternalPendingSubmission,1:Freelancer,2:ExternalAccess}
     */
    private function pending(User $inviter): array
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
        ExternalPendingFieldChange::create([
            'submission_id' => $submission->id,
            'target_type' => $fl->getMorphClass(),
            'target_id' => $fl->id,
            'field_key' => 'zip_code',
            'old_value' => null,
            'new_value' => '10117',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        return [$submission, $fl, $external];
    }

    #[Test]
    public function inviter_can_view_submission(): void
    {
        $inviter = User::factory()->create();
        [$submission, , $external] = $this->pending($inviter);
        $this->actingAs($inviter);

        $this->get(route('crm.contacts.external-submissions.show', [$external->crm_contact_id, $submission->id]))
            ->assertOk();
    }

    #[Test]
    public function inviter_can_approve_all(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl, $external] = $this->pending($inviter);
        $this->actingAs($inviter);

        $this->post(route('crm.contacts.external-submissions.approve-all', [$external->crm_contact_id, $submission->id]))
            ->assertRedirect();

        $this->assertSame('10117', $fl->fresh()->zip_code);
        $this->assertSame(ExternalSubmissionStatus::APPROVED, $submission->fresh()->status);
    }

    #[Test]
    public function stranger_cannot_approve(): void
    {
        $inviter = User::factory()->create();
        [$submission, , $external] = $this->pending($inviter);
        $this->actingAs(User::factory()->create());

        $this->post(route('crm.contacts.external-submissions.approve-all', [$external->crm_contact_id, $submission->id]))
            ->assertForbidden();
    }

    #[Test]
    public function partial_decisions_via_http(): void
    {
        $inviter = User::factory()->create();
        [$submission, $fl, $external] = $this->pending($inviter);
        $change = $submission->fieldChanges()->first();
        $this->actingAs($inviter);

        $this->post(route('crm.contacts.external-submissions.partial-decisions', [$external->crm_contact_id, $submission->id]), [
            'decisions' => [['field_change_id' => $change->id, 'decision' => 'rejected']],
            'rejection_reason' => 'Bitte korrigieren',
        ])->assertRedirect();

        $this->assertNull($fl->fresh()->zip_code);
        $this->assertSame(ExternalSubmissionStatus::REJECTED, $submission->fresh()->status);
    }

    #[Test]
    public function submission_not_belonging_to_contact_returns_404(): void
    {
        $inviter = User::factory()->create();
        [$submission] = $this->pending($inviter);
        $otherContactId = $this->pending($inviter)[2]->crm_contact_id;
        $this->actingAs($inviter);

        $this->get(route('crm.contacts.external-submissions.show', [$otherContactId, $submission->id]))
            ->assertNotFound();
    }

    #[Test]
    public function external_submit_creates_pending_and_redirects(): void
    {
        $inviter = User::factory()->create();
        [, $fl, $external] = $this->pending($inviter);

        $this->app['auth']->guard('external')->login($external);

        $request = \Artwork\Modules\ExternalAccess\Http\Requests\SubmitCrmSelfEditRequest::create(
            route('external.crm.submit'),
            'POST',
            ['values' => ['personal' => ['zip_code' => '99999']]],
        );
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);
        $request->setContainer($this->app)->validateResolved();

        $response = app(\Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmController::class)->submit($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('external_pending_submissions', [
            'external_access_id' => $external->id,
            'status' => ExternalSubmissionStatus::PENDING->value,
        ]);
    }
}
