<?php

namespace Artwork\Modules\Scheduling\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Scheduling\Models\Scheduling;
use Artwork\Modules\Scheduling\Repositories\SchedulingRepository;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

readonly class SchedulingService
{
    public function __construct(private SchedulingRepository $schedulingRepository)
    {
    }

    public function create(
        int $userId,
        string $type,
        string $model,
        int $modelId
    ): bool {
        $scheduling = $this->schedulingRepository->getByUserIdAndTypeAndModelAndModelId(
            $userId,
            $type,
            $model,
            $modelId
        );

        if ($scheduling instanceof Scheduling) {
            $scheduling->increment('count');

            return true;
        }

        $this->schedulingRepository->save(
            new Scheduling(
                [
                    'user_id' => $userId,
                    'count' => 1,
                    'type' => $type,
                    'model' => $model,
                    'model_id' => $modelId
                ]
            )
        );

        return true;
    }

    //@todo: fix phpcs error - refactor function because nesting level and complexity is too high
    //phpcs:ignore Generic.Metrics.NestingLevel.TooHigh, Generic.Metrics.CyclomaticComplexity.TooHigh
    public function sendNotification(
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        $schedulingsToNotify = $this->schedulingRepository->getAllWhereUpdatedAtLowerOrEqualThan(
            Carbon::now()->addMinutes(30)->setTimezone(config('app.timezone'))
        );

        foreach ($schedulingsToNotify as $schedulings) {
            $user = User::find($schedulings->user_id);
            switch ($schedulings->type) {
                case 'TASK_ADDED':
                    $notificationTitle = __(
                        'notification.scheduling.new_tasks',
                        ['count' => $schedulings->count],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_NEW_TASK);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'PROJECT_CHANGES':
                    $project = Project::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.changes_project',
                        ['project' => $project->name],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setShowHistory(true);
                    $notificationService->setHistoryType('project');
                    $notificationService->setModelId($project->id);
                    $notificationService->setProjectId($project->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'TASK_CHANGES':
                    $task = Task::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.changes_task',
                        ['task' => $task?->name],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('blue');
                    $notificationService->setPriority(1);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TASK_CHANGED);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setTaskId($task->id);
                    $notificationService->setButtons(['showInTasks']);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'ROOM_CHANGES':
                    $room = Room::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.changes_room',
                        ['room' => $room?->name],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setRoomId($room->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'EVENT_CHANGES':
                    $event = Event::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.changes_event',
                        [],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $room = $event->room()->first();

                    $notificationDescription = [
                        1 => [
                            'type' => 'link',
                            'title' => $room ? $room->name : '',
                            'href' => route('rooms.show', $room ? $room->id : null)
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                            'href' => null
                        ],
                        3 => [
                            'type' => 'link',
                            'title' => $event->project()->first() ? $event->project()->first()->name : '',
                            'href' => $event->project()->first() ?
                                route(
                                    'projects.tab',
                                    [
                                        $event->project->id,
                                        $projectTabService->findFirstProjectTabWithCalendarComponent()?->id
                                    ]
                                ) :
                                null
                        ],
                        4 => [
                            'type' => 'string',
                            'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                                Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                            'href' => null
                        ]
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_CHANGED);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setShowHistory(true);
                    $notificationService->setHistoryType('event');
                    $notificationService->setModelId($event->id);
                    $notificationService->setDescription($notificationDescription);
                    $notificationService->setEventId($event->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'PUBLIC_CHANGES':
                    $project = Project::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.public_changes_project',
                        ['project' => $project->name],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PUBLIC_RELEVANT);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setProjectId($project->id);
                    $notificationService->setShowHistory(true);
                    $notificationService->setHistoryType('project');
                    $notificationService->setModelId($project->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    break;
                case 'VACATION_CHANGES':
                    $user = User::find($schedulings->model_id);
                    $notificationTitle = __(
                        'notification.scheduling.changes_vacation',
                        [],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => random_int(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setIcon('green');
                    $notificationService->setPriority(3);
                    $notificationService
                        ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_AVAILABLE);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setShowHistory(true);
                    $notificationService->setHistoryType('vacations');
                    $notificationService->setModelId($user->id);
                    $notificationService->setNotificationTo($user);
                    $notificationService->createNotification();
                    $crafts = $user->crafts()->get();
                    foreach ($crafts as $craft) {
                        foreach ($craft->users()->get() as $craftUser) {
                            if ($craftUser->id === $user->id) {
                                continue;
                            }
                            $notificationService->setNotificationTo($craftUser);
                            $notificationService->createNotification();
                        }
                    }
                    break;
            }
            $schedulings->delete();
        }
    }
}
