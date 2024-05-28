<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyCraftIfShiftDeadlineReached extends Command
{
    protected $signature = 'artwork:notify-craft-if-shift-deadline-reached';

    protected $description = 'Notify craft if shift deadline reached.';

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->setIcon('red');
        $notificationService->setPriority(2);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND);

        $crafts = Craft::with(['shifts.users', 'shifts.freelancer', 'shifts.serviceProvider'])->get();

        foreach ($crafts as $craft) {
            $this->processCraft($craft, $notificationService);
        }
    }

    private function processCraft($craft, NotificationService $notificationService): void
    {
        $shifts = $craft->shifts()->whereDate('start_date', now()->addDays($craft->notify_days))->get();

        foreach ($shifts as $shift) {
            $this->processShift($shift, $craft, $notificationService);
        }
    }

    private function processShift($shift, $craft, NotificationService $notificationService): void
    {
        $completeUserCount = $shift->users->count() + $shift->freelancer->count() + $shift->serviceProvider->count();

        if ($completeUserCount < $shift->max_users) {
            $event = $shift->event;
            $project = $event->project;
            $remainingUsersCount = $shift->max_users - $completeUserCount;

            $notificationService->setButtons(['show_project']);
            $notificationService->setProjectId($project->id);

            $contactedUsers = $this->notifyProjectManagers(
                $project->managerUsers,
                $notificationService,
                $event,
                $remainingUsersCount,
                $craft,
                $shift
            );

            if (!$craft->assignable_by_all) {
                $this->notifyCraftUsers(
                    $craft->users,
                    $contactedUsers,
                    $notificationService,
                    $event,
                    $remainingUsersCount,
                    $craft,
                    $shift
                );
            }
        }
    }

    /**
     * @param $projectManagers
     * @param NotificationService $notificationService
     * @param $event
     * @param $remainingUsersCount
     * @param $craft
     * @param $shift
     * @return string []
     */
    private function notifyProjectManagers(
        $projectManagers,
        NotificationService $notificationService,
        $event,
        $remainingUsersCount,
        $craft,
        $shift
    ): array {
        $contactedUsers = [];

        foreach ($projectManagers as $projectManager) {
            $this->sendNotification(
                $notificationService,
                $projectManager,
                $event,
                $remainingUsersCount,
                $craft,
                $shift
            );
            $contactedUsers[] = $projectManager->id;
        }

        return $contactedUsers;
    }

    private function notifyCraftUsers(
        $craftUsers,
        $contactedUsers,
        NotificationService $notificationService,
        $event,
        $remainingUsersCount,
        $craft,
        $shift
    ): void {
        foreach ($craftUsers as $user) {
            if (!in_array($user->id, $contactedUsers, true)) {
                $this->sendNotification($notificationService, $user, $event, $remainingUsersCount, $craft, $shift);
                $contactedUsers[] = $user->id;
            }
        }
    }

    private function sendNotification(
        NotificationService $notificationService,
        $user,
        $event,
        $remainingUsersCount,
        $craft,
        $shift
    ): void {
        $notificationTitle = __(
            'notification.shift.open_demand',
            [
                'event' => $event->name ?? $event->eventName,
                'count' => $remainingUsersCount
            ],
            $user->language
        );

        $notificationService->setTitle($notificationTitle);
        $notificationService->setBroadcastMessage([
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ]);
        $notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => __(
                    'notification.shift.open_demand_description',
                    [
                        'event' => $event->name ?? $event->eventName,
                        'count' => $remainingUsersCount,
                        'craft' => $craft->name . ' (' . $craft->abbreviation . ')',
                        'shift' => $shift->start . ' - ' . $shift->end,
                    ],
                    $user->language
                ),
                'href' => null
            ],
        ]);
        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
    }
}
