<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Notifications\ExternalReviewResultNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReviewResultNotificationTest extends TestCase
{
    private function submission(ExternalSubmissionStatus $status): ExternalPendingSubmission
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $external = ExternalAccess::factory()->create();

        return ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    #[Test]
    public function uses_only_mail_channel(): void
    {
        $notification = new ExternalReviewResultNotification($this->submission(ExternalSubmissionStatus::APPROVED));

        $this->assertSame(['mail'], $notification->via((object) []));
    }

    #[Test]
    public function subject_reflects_status(): void
    {
        $approved = new ExternalReviewResultNotification($this->submission(ExternalSubmissionStatus::APPROVED));
        $rejected = new ExternalReviewResultNotification($this->submission(ExternalSubmissionStatus::REJECTED));
        $partial = new ExternalReviewResultNotification($this->submission(ExternalSubmissionStatus::PARTIALLY_APPROVED));

        $this->assertStringContainsString('accepted', (string) $approved->toMail((object) [])->subject);
        $this->assertStringContainsString('declined', (string) $rejected->toMail((object) [])->subject);
        $this->assertStringContainsString('partially', (string) $partial->toMail((object) [])->subject);
    }
}
