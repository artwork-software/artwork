<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Events\NewNotificationBroadcast;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die planende Person bekam beim Einplanen einer anderen Person mit zu kurzer
 * Ruhezeit den Toast "Du wurdest mit zu kurzer Ruhezeit eingeplant", weil die
 * Broadcast-Nachricht der Mitarbeitenden-Benachrichtigung nicht überschrieben wurde.
 * (Eine gespeicherte Benachrichtigung bekommt die handelnde Person nie — der
 * NotificationService überspringt Auth::id(); es bleibt der Live-Toast.)
 */
final class ShortBreakPlannerNotificationTest extends FeatureTestCase
{
    #[Test]
    public function planner_toast_says_person_scheduled_not_you_were_scheduled(): void
    {
        $planner = $this->actingAsAdmin();
        $planner->update(['language' => 'de']);
        $this->enablePush($planner, NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
        $this->enablePush($planner, NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);

        $worker = User::factory()->create(['language' => 'de']);
        $this->enablePush($worker, NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);

        $qualification = ShiftQualification::factory()->create();
        $first = $this->committedShiftAt('2026-06-08', '18:00:00', '23:00:00', $qualification);
        $second = $this->committedShiftAt('2026-06-09', '06:00:00', '10:00:00', $qualification);

        $service = app(ShiftWorkerService::class);
        $service->assignToShift($first, $worker, $qualification->id, 'TST', app(NotificationService::class));

        Notification::fake();
        Event::fake([NewNotificationBroadcast::class]);
        $service->assignToShift($second, $worker, $qualification->id, 'TST', app(NotificationService::class));

        // Eingeplante Person: gespeicherte Benachrichtigung + Toast "Du wurdest …"
        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            fn (ShiftNotification $n) => $n->toArray()->title === 'Du wurdest mit zu kurzer Ruhezeit eingeplant'
        );
        Event::assertDispatched(
            NewNotificationBroadcast::class,
            fn (NewNotificationBroadcast $e) => $e->user->id === $worker->id
                && $e->message['message'] === 'Du wurdest mit zu kurzer Ruhezeit eingeplant'
        );

        // Planende Person: Toast "Person mit zu kurzer Ruhezeit eingeplant", nie "Du wurdest …"
        Event::assertDispatched(
            NewNotificationBroadcast::class,
            fn (NewNotificationBroadcast $e) => $e->user->id === $planner->id
                && $e->message['message'] === 'Person mit zu kurzer Ruhezeit eingeplant'
        );
        Event::assertNotDispatched(
            NewNotificationBroadcast::class,
            fn (NewNotificationBroadcast $e) => $e->user->id === $planner->id
                && $e->message['message'] === 'Du wurdest mit zu kurzer Ruhezeit eingeplant'
        );
    }

    private function enablePush(User $user, NotificationEnum $type): void
    {
        $user->notificationSettings()->create([
            'group_type' => $type->groupType(),
            'type' => $type->value,
            'title' => $type->title(),
            'description' => $type->description(),
            'enabled_push' => true,
        ]);
    }

    private function committedShiftAt(
        string $day,
        string $start,
        string $end,
        ShiftQualification $qualification
    ): Shift {
        $shift = Shift::factory()->create([
            'is_committed' => true,
            'event_start_day' => $day,
            'event_end_day' => $day,
            'start_date' => $day,
            'end_date' => $day,
            'start' => $start,
            'end' => $end,
        ]);
        $shift->shiftsQualifications()->create(['shift_qualification_id' => $qualification->id, 'value' => 1]);

        return $shift;
    }
}
