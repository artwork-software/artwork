<?php

namespace Artwork\Modules\Scheduling\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Scheduling\Models\Scheduling;
use Artwork\Modules\Scheduling\Repositories\SchedulingRepository;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

class SchedulingService
{
    public function __construct(
        private readonly SchedulingRepository $schedulingRepository,
        private readonly LoggerInterface $logger,
        private readonly CarbonService $carbonService,
    ) {
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
            $scheduling->__call('increment', ['count']);

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
    //phpcs:ignore Generic.Metrics.NestingLevel.TooHigh, Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function sendNotification(
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        $this->logger->debug('Send notifications: ' . $this->carbonService->getNow()->format('Y-m-d H:i:s'));

        $schedulingsToNotify = $this->schedulingRepository->getAllWhereUpdatedAtLowerOrEqualThan(
            $this->carbonService->getNow()->addMinutes(30)->setTimezone(config('app.timezone'))
        );

        foreach ($schedulingsToNotify as $schedulings) {
            $user = User::query()->find($schedulings->user_id);
            if (!$user instanceof User) {
                $this->logger->error('User with id: ' . $schedulings->user_id . ' not found.');
                continue;
            }

            $this->logger->debug('Attempt to send scheduling type: ' . $schedulings->type);
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
                    $project = Project::query()->find($schedulings->model_id);
                    if (!$project instanceof Project) {
                        $this->logger->error('Project with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
                    $notificationTitle = __(
                        'notification.scheduling.changes_project',
                        ['project' => $project?->name ?? 'Project name not found'],
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
                    $task = Task::query()->find($schedulings->model_id);
                    if (!$task instanceof Task) {
                        $this->logger->error('Task with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
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
                    $room = Room::query()->find($schedulings->model_id);
                    if (!$room instanceof Room) {
                        $this->logger->error('Room with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
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
                    $event = Event::query()->find($schedulings->model_id);
                    if (!$event instanceof Event) {
                        $this->logger->error('Event with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
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
                                        $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                            ProjectTabComponentEnum::CALENDAR
                                        )
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
                    $project = Project::query()->find($schedulings->model_id);
                    if (!$project instanceof Project) {
                        $this->logger->error('Project with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
                    $notificationTitle = __(
                        'notification.scheduling.public_changes_project',
                        ['project' => $project?->name],
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
                    $user = User::query()->find($schedulings->model_id);
                    if (!$user instanceof User) {
                        $this->logger->error('User with id: ' . $schedulings->model_id . ' not found.');
                        break;
                    }
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
            $this->logger->debug(
                sprintf(
                    'Successfully deleted scheduling model with id: %d after createNotification().',
                    $schedulings->id
                )
            );
        }
        $this->logger->debug(__CLASS__ . ' done.');
    }
}
