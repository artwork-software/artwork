<?php

namespace Tests\Feature\Console;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Rückgabe-Erinnerung folgt derselben Regel wie Badge und Verfügbarkeit: Altbestand vor der
 * Rückgabe-Erfassung (ExternalIssue::returnTrackingSince()) gilt als zurückgegeben und wird nicht erinnert.
 */
final class SendExternalIssueReturnDueNotificationsCommandTest extends FeatureTestCase
{
    #[Test]
    public function overdue_tracked_issues_are_reminded_but_legacy_issues_are_not(): void
    {
        $issuer = User::factory()->create();
        $legacy = ExternalIssue::factory()->create([
            'issued_by_id' => $issuer->id,
            'issue_date' => '2026-05-01',
            'return_date' => '2026-05-10',
            'return_status' => null,
            'received_by_id' => null,
            'return_notification_sent_at' => null,
            'created_at' => '2026-05-01 10:00:00',
        ]);
        $tracked = ExternalIssue::factory()->create([
            'issued_by_id' => $issuer->id,
            'issue_date' => Carbon::today()->subDays(5)->toDateString(),
            'return_date' => Carbon::today()->subDay()->toDateString(),
            'return_status' => null,
            'received_by_id' => null,
            'return_notification_sent_at' => null,
        ]);

        $this->artisan('artwork:send-external-issue-return-due-notifications')->assertSuccessful();

        $this->assertNull($legacy->fresh()->return_notification_sent_at);
        $this->assertNotNull($tracked->fresh()->return_notification_sent_at);
    }
}
