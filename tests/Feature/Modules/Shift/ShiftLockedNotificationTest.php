<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 3a: "Dienstplan festgeschrieben" — beim Genehmigen einer Freigabe-Anfrage
 * (Workflow-Modus) bekommt jede Person mit Schicht im Gewerk/KW genau EINE
 * SHIFT_LOCKED-Notification mit Link auf den eigenen Einsatzplan; die
 * Bulk-Festschreibung (Direkt-Modus) sendet denselben Text statt shift_staffing.
 */
final class ShiftLockedNotificationTest extends FeatureTestCase
{
    private function createPendingRequest(Craft $craft, User $requester): ShiftPlanRequest
    {
        return ShiftPlanRequest::create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);
    }

    private function createShiftInRequest(Craft $craft, ?ShiftPlanRequest $request, string $date = '2026-05-06'): Shift
    {
        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => $request !== null,
            'current_request_id' => $request?->id,
        ]);
    }

    private function assignUser(Shift $shift, User $user): void
    {
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);
    }

    private function isLockedNotification(ShiftNotification $notification): bool
    {
        return $notification->toArray()->type === NotificationEnum::NOTIFICATION_SHIFT_LOCKED;
    }

    #[Test]
    public function accepting_a_request_notifies_each_worker_with_a_shift_exactly_once(): void
    {
        $requester = User::factory()->create(['language' => 'de']);
        $this->actingAsAdmin();
        $craft = Craft::factory()->create(['name' => 'Technik']);
        $request = $this->createPendingRequest($craft, $requester);

        $worker = User::factory()->create(['language' => 'de']);
        // Zwei Schichten in derselben Anfrage → trotzdem nur EINE Notification
        $this->assignUser($this->createShiftInRequest($craft, $request, '2026-05-05'), $worker);
        $this->assignUser($this->createShiftInRequest($craft, $request, '2026-05-07'), $worker);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            fn (ShiftNotification $notification) => $this->isLockedNotification($notification)
        );
        $this->assertCount(
            1,
            Notification::sent($worker, ShiftNotification::class)
                ->filter(fn (ShiftNotification $notification) => $this->isLockedNotification($notification))
        );
    }

    #[Test]
    public function locked_notification_names_craft_and_week_and_links_to_own_operation_plan(): void
    {
        $requester = User::factory()->create(['language' => 'de']);
        $this->actingAsAdmin();
        $craft = Craft::factory()->create(['name' => 'Technik']);
        $request = $this->createPendingRequest($craft, $requester);
        $worker = User::factory()->create(['language' => 'de']);
        $this->assignUser($this->createShiftInRequest($craft, $request), $worker);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            function (ShiftNotification $notification) use ($worker): bool {
                if (!$this->isLockedNotification($notification)) {
                    return false;
                }
                $data = $notification->toArray();
                $expectedTitle = __('notification.shift.locked_craft_week', [
                    'craft' => 'Technik',
                    'week' => 19,
                    'year' => 2026,
                ], $worker->language);
                $hrefs = collect($data->description)->pluck('href')->filter();

                return $data->title === $expectedTitle
                    && $hrefs->isNotEmpty()
                    && $hrefs->every(
                        fn ($href) => str_contains($href, 'operation/plan')
                            && str_contains($href, '/' . $worker->id . '/')
                            && str_contains($href, 'start_date=2026-05-04')
                            && str_contains($href, 'end_date=2026-05-10')
                    );
            }
        );
    }

    #[Test]
    public function workers_without_a_shift_in_the_request_are_not_notified(): void
    {
        $requester = User::factory()->create(['language' => 'de']);
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();
        $request = $this->createPendingRequest($craft, $requester);
        $this->createShiftInRequest($craft, $request);

        // Person ohne Schicht
        $idle = User::factory()->create(['language' => 'de']);
        // Person mit Schicht in ANDEREM Gewerk derselben KW
        $otherCraftWorker = User::factory()->create(['language' => 'de']);
        $this->assignUser($this->createShiftInRequest($otherCraft, null), $otherCraftWorker);
        // Person mit Schicht im Gewerk, aber außerhalb der Anfrage (andere KW, nicht Teil der Anfrage)
        $otherWeekWorker = User::factory()->create(['language' => 'de']);
        $this->assignUser($this->createShiftInRequest($craft, null, '2026-05-13'), $otherWeekWorker);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        foreach ([$idle, $otherCraftWorker, $otherWeekWorker] as $user) {
            Notification::assertNotSentTo($user, ShiftNotification::class);
        }
    }

    #[Test]
    public function bulk_commit_sends_the_locked_text_instead_of_shift_staffing(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create(['name' => 'Bühne']);
        $shift = $this->createShiftInRequest($craft, null);
        $worker = User::factory()->create(['language' => 'de']);
        $this->assignUser($shift, $worker);

        $this->patch(route('update.shift.commitment'), [
            'project_id' => Project::factory()->create()->id,
            'shifts' => [$shift->id],
            'is_committed' => true,
            'committing_user_id' => $admin->id,
        ])->assertRedirect();

        $this->assertTrue((bool) $shift->fresh()->is_committed);

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            function (ShiftNotification $notification) use ($worker): bool {
                $data = $notification->toArray();
                $expectedTitle = __('notification.shift.locked_craft_week', [
                    'craft' => 'Bühne',
                    'week' => 19,
                    'year' => 2026,
                ], $worker->language);
                $staffingTitle = __('notification.shift.shift_staffing', [
                    'projectName' => '',
                    'craftAbbreviation' => '',
                ], $worker->language);

                return $this->isLockedNotification($notification)
                    && $data->title === $expectedTitle
                    && $data->title !== $staffingTitle
                    && collect($data->description)->pluck('href')->filter()
                        ->every(fn ($href) => str_contains($href, 'operation/plan'));
            }
        );
    }

    #[Test]
    public function releasing_a_commitment_sends_no_notification(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $shift = $this->createShiftInRequest($craft, null);
        $shift->forceFill(['is_committed' => true, 'committing_user_id' => $admin->id])->save();
        $worker = User::factory()->create(['language' => 'de']);
        $this->assignUser($shift, $worker);

        $this->patch(route('update.shift.commitment'), [
            'project_id' => Project::factory()->create()->id,
            'shifts' => [$shift->id],
            'is_committed' => false,
            'committing_user_id' => null,
        ])->assertRedirect();

        $this->assertFalse((bool) $shift->fresh()->is_committed);
        Notification::assertNotSentTo($worker, ShiftNotification::class);
    }
}
