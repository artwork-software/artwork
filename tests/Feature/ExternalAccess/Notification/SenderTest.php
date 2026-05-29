<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Notifications\ExternalCrmSubmissionNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalReviewResultNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationSender;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SenderTest extends TestCase
{
    private function sender(): ExternalNotificationSender
    {
        return app(ExternalNotificationSender::class);
    }

    private function externalWithContact(?User $inviter): ExternalAccess
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $fl->createCrmContact();

        return ExternalAccess::factory()->create([
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter?->id,
        ]);
    }

    private function submission(ExternalAccess $external, ExternalSubmissionStatus $status = ExternalSubmissionStatus::PENDING): ExternalPendingSubmission
    {
        return ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    #[Test]
    public function crm_submission_dispatches_to_inviter(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $external = $this->externalWithContact($inviter);

        $this->sender()->notifyCrmSubmissionCreated($this->submission($external), true);

        Notification::assertSentTo($inviter, ExternalCrmSubmissionNotification::class);
    }

    #[Test]
    public function crm_submission_uses_first_time_phrase_when_initial(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $external = $this->externalWithContact($inviter);

        $this->sender()->notifyCrmSubmissionCreated($this->submission($external), true);

        Notification::assertSentTo(
            $inviter,
            ExternalCrmSubmissionNotification::class,
            fn ($notification) => str_contains($notification->toArray()->title, 'first time'),
        );
    }

    #[Test]
    public function crm_submission_uses_update_phrase_when_not_initial(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $external = $this->externalWithContact($inviter);

        $this->sender()->notifyCrmSubmissionCreated($this->submission($external), false);

        Notification::assertSentTo(
            $inviter,
            ExternalCrmSubmissionNotification::class,
            fn ($notification) => str_contains($notification->toArray()->title, 'updated their data'),
        );
    }

    #[Test]
    public function external_review_result_goes_to_external_user_only_via_mail(): void
    {
        Notification::fake();
        $external = $this->externalWithContact(null);
        $submission = $this->submission($external, ExternalSubmissionStatus::APPROVED);

        $this->sender()->notifyExternalReviewResult($submission);

        Notification::assertSentTo(
            $external,
            ExternalReviewResultNotification::class,
            fn ($notification, $channels) => $channels === ['mail'],
        );
    }

    #[Test]
    public function tab_component_update_notifies_inviter_and_manager(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $manager = User::factory()->create();
        $external = $this->externalWithContact($inviter);
        $project = Project::factory()->create();
        $project->users()->attach($manager->id, ['is_manager' => true]);
        $tab = ProjectTab::factory()->create();
        $component = new Component(['name' => 'Notes']); // unsaved; sender only reads ->name

        $this->sender()->notifyTabComponentUpdated($external, $project, $tab, $component);

        Notification::assertSentTo($inviter, ExternalTabComponentUpdatedNotification::class);
        Notification::assertSentTo($manager, ExternalTabComponentUpdatedNotification::class);
    }
}
