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
        $contactedUsers = [];
        $crafts = Craft::with(['shifts.users', 'shifts.freelancer', 'shifts.serviceProvider'])->get();

        $notificationService->setIcon('red');
        $notificationService->setPriority(2);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND);

        foreach ($crafts as $craft) {
            $shifts = $craft->shifts()->whereDate('start_date', now()->addDays($craft->notify_days))->get();
            foreach ($shifts as $shift) {
                $completeUserCount = $shift->users->count() + $shift
                        ->freelancer->count() + $shift->serviceProvider->count();

                if ($completeUserCount < $shift->max_users) {
                    $event = $shift->event;
                    $project = $event->project;
                    $projectManagers = $project->managerUsers;

                    $notificationService->setButtons(['show_project']);
                    $notificationService->setProjectId($project->id);

                    $remainingUsersCount = $shift->max_users - $completeUserCount;

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

                    if (!$craft->assignable_by_all) {
                        foreach ($craft->users as $user) {
                            if (!in_array($user->id, $contactedUsers)) {
                                $this->sendNotification(
                                    $notificationService,
                                    $user,
                                    $event,
                                    $remainingUsersCount,
                                    $craft,
                                    $shift
                                );
                                $contactedUsers[] = $user->id;
                            }
                        }
                    }
                }
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
