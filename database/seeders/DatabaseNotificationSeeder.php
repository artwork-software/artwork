<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseNotificationSeeder extends Seeder
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly UserService $userService
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function run(): void
    {
        $event = Event::query()
            ->with(['project.departments', 'room'])
            ->whereNotNull('project_id')
            ->first();
        $room = $event->getAttribute('room');
        $project = $event->getAttribute('project');

        $values = [
            'description' => [
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
                    'href' => 'http://google.de'
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ]
            ],
            'icon' => 'gray',
            'showHistory' => false,
            'historyType' => '',
            'modelId' => Event::query()->first()->getAttribute('id'),
            'broadcastMessage' => [],
            'roomId' => $room->getAttribute('id'),
            'eventId' => $event->getAttribute('id'),
            'projectId' => $project->getAttribute('id'),
            'departmentId' => $project->getAttribute('departments')->first()?->getAttribute('id'),
            'taskId' => null,
            'budgetData' => null,
            'shiftId' => Shift::query()->first()->getAttribute('id'),
        ];

        $this->notificationService->setDescription($values['description']);
        $this->notificationService->setIcon($values['icon']);
        $this->notificationService->setShowHistory($values['showHistory']);
        $this->notificationService->setHistoryType($values['historyType']);
        $this->notificationService->setModelId($values['modelId']);
        $this->notificationService->setBroadcastMessage($values['broadcastMessage']);
        $this->notificationService->setRoomId($values['roomId']);
        $this->notificationService->setEventId($values['eventId']);
        $this->notificationService->setProjectId($values['projectId']);
        $this->notificationService->setDepartmentId($values['departmentId']);
        $this->notificationService->setTaskId($values['taskId']);
        $this->notificationService->setBudgetData($values['budgetData']);
        $this->notificationService->setShiftId($values['shiftId']);

        foreach ($this->userService->getAllUsers() as $user) {
            $this->notificationService->setNotificationTo($user);
            foreach (NotificationEnum::cases() as $notificationEnum) {
                $this->notificationService->setTitle(fake()->name);
                $this->notificationService->setNotificationConstEnum($notificationEnum);
                $this->notificationService->setNotificationKey(Str::random(15));

                switch ($notificationEnum) {
                    case NotificationEnum::NOTIFICATION_ROOM_REQUEST:
                        $this->notificationService->setButtons(['accept', 'decline']);
                        break;
                    case NotificationEnum::NOTIFICATION_ROOM_ANSWER:
                        $this->notificationService->setButtons(['answer', 'answerDialog']);
                        break;
                    case NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
                        $this->notificationService->setButtons(['change_request', 'event_delete']);
                        break;
                    case NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
                        $this->notificationService->setButtons(['calculation_check', 'delete_request']);
                        $this->notificationService->setPositionVerifyRequestId(
                            MainPosition::query()->find(10)->getAttribute('id')
                        );
                        $this->notificationService->setPositionVerifyRequestType('main');
                        break;
                    case NotificationEnum::NOTIFICATION_SHIFT_CONFLICT:
                        $this->notificationService->setButtons(['see_shift']);
                        break;
                    case NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND:
                        $this->notificationService->setButtons(['show_project']);
                        break;
                    case NotificationEnum::NOTIFICATION_TASK_CHANGED:
                        $this->notificationService->setButtons(['showInTasks']);
                        break;
                }

                $this->notificationService->createNotification();
            }
        }
    }
}
