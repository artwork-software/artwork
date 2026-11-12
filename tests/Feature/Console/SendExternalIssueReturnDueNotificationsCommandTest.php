<?php

namespace Tests\Feature\Console;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\Inventory\Notifications\InventoryArticleNotification;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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

    #[Test]
    public function reminder_link_leads_to_a_target_the_responsible_person_may_open(): void
    {
        $issuerWithoutDisposition = User::factory()->create();
        $disponent = User::factory()->create();
        $disponent->givePermissionTo(PermissionEnum::INVENTORY_DISPOSITION->value);

        $issueOfIssuer = $this->createDueIssue($issuerWithoutDisposition);
        $issueOfDisponent = $this->createDueIssue($disponent);

        $this->artisan('artwork:send-external-issue-return-due-notifications')->assertSuccessful();

        $this->assertSame(
            route('extern-issue-of-material.print', ['externalIssue' => $issueOfIssuer->id, 'preview' => 1]),
            $this->reminderLinkOf($issuerWithoutDisposition)
        );
        $this->assertSame(
            route('extern-issue-of-material.index', ['issue' => $issueOfDisponent->id]),
            $this->reminderLinkOf($disponent)
        );

        $this->assertTrue($issuerWithoutDisposition->can('view', $issueOfIssuer));
        $this->assertFalse($issuerWithoutDisposition->can('viewAny', ExternalIssue::class));
    }

    private function createDueIssue(User $issuer): ExternalIssue
    {
        return ExternalIssue::factory()->create([
            'issued_by_id' => $issuer->id,
            'project_id' => null,
            'issue_date' => Carbon::today()->subDays(5)->toDateString(),
            'return_date' => Carbon::today()->subDay()->toDateString(),
            'return_status' => null,
            'received_by_id' => null,
            'return_notification_sent_at' => null,
        ]);
    }

    private function reminderLinkOf(User $user): ?string
    {
        $link = null;
        Notification::assertSentTo(
            $user,
            InventoryArticleNotification::class,
            function (InventoryArticleNotification $notification) use (&$link): bool {
                $link = collect($notification->toArray()->description)->firstWhere('type', 'link')['href'] ?? null;

                return true;
            }
        );

        return $link;
    }
}
