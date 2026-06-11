<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welle-5-Regression (B35): Das Live-Workflow-System (ShiftPlanRequest) benachrichtigt
 * jetzt Genehmiger bei neuen Anfragen und den Antragsteller bei Entscheidungen —
 * das tat vorher nur der entfernte Alt-Store.
 */
final class ShiftWorkflowNotificationTest extends FeatureTestCase
{
    #[Test]
    public function new_request_notifies_workflow_users(): void
    {
        $this->actingAsAdmin();
        $approver = User::factory()->create();
        ShiftCommitWorkflowUser::create(['user_id' => $approver->id]);
        $craft = Craft::factory()->create();
        Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ]);

        Notification::assertSentTo($approver, ShiftNotification::class);
    }

    #[Test]
    public function accept_notifies_requester(): void
    {
        $requester = User::factory()->create();
        $this->actingAsAdmin();
        $request = ShiftPlanRequest::create([
            'craft_id' => Craft::factory()->create()->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);

        $this->post(route('shift-plan-requests.accept', $request));

        Notification::assertSentTo($requester, ShiftNotification::class);
    }

    #[Test]
    public function reject_notifies_requester(): void
    {
        $requester = User::factory()->create();
        $this->actingAsAdmin();
        $request = ShiftPlanRequest::create([
            'craft_id' => Craft::factory()->create()->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);

        $this->post(route('shift-plan-requests.reject', $request), []);

        Notification::assertSentTo($requester, ShiftNotification::class);
    }

    #[Test]
    public function already_processed_request_sends_no_decision_notification(): void
    {
        $requester = User::factory()->create();
        $this->actingAsAdmin();
        $request = ShiftPlanRequest::create([
            'craft_id' => Craft::factory()->create()->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'rejected',
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);

        $this->post(route('shift-plan-requests.accept', $request));

        Notification::assertNotSentTo($requester, ShiftNotification::class);
    }
}
