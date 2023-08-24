<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\Department;
use App\Models\Event;
use App\Models\Project;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use App\Notifications\BudgetVerified;
use App\Notifications\ConflictNotification;
use App\Notifications\DeadlineNotification;
use App\Notifications\EventNotification;
use App\Notifications\MoneySourceNotification;
use App\Notifications\ProjectNotification;
use App\Notifications\RoomNotification;
use App\Notifications\RoomRequestNotification;
use App\Notifications\ShiftNotification;
use App\Notifications\TaskNotification;
use App\Notifications\TeamNotification;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Ramsey\Uuid\Type\Integer;

class NotificationService
{

    public User $notificationTo;
    public String $title;
    public array|null $description = [];
    public ?NotificationConstEnum $notificationConstEnum = null;
    public String $icon = 'green';
    public array $buttons = [];
    public bool $showHistory = false;
    public String $historyType = '';
    public int|null $modelId = null;

    public array|null $broadcastMessage = [];

    public int|null $roomId = null;

    public int|null $eventId = null;

    public int|null $projectId = null;
    public int|null $departmentId = null;
    public int|null $taskId = null;
    public object|null $budgetData = null;

    public function getNotificationTo(): User
    {
        return $this->notificationTo;
    }

    public function setNotificationTo(User $notificationTo): void
    {
        $this->notificationTo = $notificationTo;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function setDescription(?array $description): void
    {
        $this->description = $description;
    }

    public function getNotificationConstEnum(): ?NotificationConstEnum
    {
        return $this->notificationConstEnum;
    }

    public function setNotificationConstEnum(?NotificationConstEnum $notificationConstEnum): void
    {
        $this->notificationConstEnum = $notificationConstEnum;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }

    public function isShowHistory(): bool
    {
        return $this->showHistory;
    }

    public function setShowHistory(bool $showHistory): void
    {
        $this->showHistory = $showHistory;
    }

    public function getHistoryType(): string
    {
        return $this->historyType;
    }

    public function setHistoryType(string $historyType): void
    {
        $this->historyType = $historyType;
    }

    public function getModelId(): ?int
    {
        return $this->modelId;
    }

    public function setModelId(?int $modelId): void
    {
        $this->modelId = $modelId;
    }

    public function getBroadcastMessage(): ?array
    {
        return $this->broadcastMessage;
    }

    public function setBroadcastMessage(?array $broadcastMessage): void
    {
        $this->broadcastMessage = $broadcastMessage;
    }

    public function getRoomId(): ?int
    {
        return $this->roomId;
    }

    public function setRoomId(?int $roomId): void
    {
        $this->roomId = $roomId;
    }

    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    public function setEventId(?int $eventId): void
    {
        $this->eventId = $eventId;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(?int $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(?int $departmentId): void
    {
        $this->departmentId = $departmentId;
    }

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getBudgetData(): ?object
    {
        return $this->budgetData;
    }

    public function setBudgetData(?object $budgetData): void
    {
        $this->budgetData = $budgetData;
    }


    protected string $notificationKey = '';

    /**
     * @return string
     */
    public function getNotificationKey(): string
    {
        return $this->notificationKey;
    }

    /**
     * @param string $notificationKey
     */
    public function setNotificationKey(string $notificationKey): void
    {
        $this->notificationKey = $notificationKey;
    }


    /**
     * show the form for creating a new resource.
     *
     * @param $user
     * @param object $notificationData
     * @param array|null $broadcastMessage
     * @return void
     */
    public function create($user, object $notificationData, ?array $broadcastMessage = []): void
    {
        /*$notificationBody = [];
        $notificationBodyNew = new \stdClass();
        switch ($notificationData->type) {
            case NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'accepted' => $notificationData->accepted,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomRequestNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT_CHANGED:

                $historyArray = [];
                $historyComplete = $notificationData->event->historyChanges()->all();

                foreach ($historyComplete as $history){
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }

                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'event' => $notificationData->event,
                    'eventHistory' => $historyArray,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new EventNotification($notificationBody, $broadcastMessage));
                break;

            case NotificationConstEnum::NOTIFICATION_TASK_CHANGED:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => [
                        'title' => $notificationData->task->title,
                        'deadline' => $notificationData->task->deadline
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;

            case NotificationConstEnum::NOTIFICATION_PROJECT:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'team' => [
                        'id' => $notificationData->team->id,
                        'title' => $notificationData->team->title,
                        'svg_name' => $notificationData->team->svg_name,
                    ],
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new TeamNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_CHANGED:
                $notificationBodyNew->groupType = 'ROOMS';
                $notificationBodyNew->type = $notificationData->type;
                $notificationBodyNew->title = $notificationData->title;
                $notificationBodyNew->room = $notificationData->room;
                $notificationBodyNew->created_by = $notificationData->created_by;

                $notificationBody = [
                    'groupType' => 'ROOMS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'room' => $notificationData->room,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new RoomNotification($notificationBodyNew, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                $notificationBody = [
                    'groupType' => 'EVENTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'conflict' => $notificationData->conflict,
                    'created_by' => $notificationData->created_by
                ];
                Notification::send($user, new ConflictNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_REMINDER:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'task' => $notificationData->task,
                ];
                Notification::send($user, new DeadlineNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_NEW_TASK:
                $notificationBody = [
                    'groupType' => 'TASKS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                ];
                Notification::send($user, new TaskNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'created_by' => $notificationData->created_by,
                ];
                Notification::send($user, new MoneySourceNotification($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
                $notificationBody = [
                    'groupType' => 'BUDGET',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'requested_position' => $notificationData->requested_position,
                    'project' => $notificationData->project,
                    'created_by' => $notificationData->created_by,
                    'requested_id' => $notificationData->requested_id,
                    'position' => $notificationData->position,
                    'changeType' => $notificationData->changeType
                ];
                Notification::send($user, new BudgetVerified($notificationBody, $broadcastMessage));
                break;
            case NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT:
                $project = $notificationData->project->id;
                $historyArray = [];
                $historyComplete = Project::find($project)->historyChanges()->all();
                foreach ($historyComplete as $history){
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
                $notificationBody = [
                    'groupType' => 'PROJECTS',
                    'type' => $notificationData->type,
                    'title' => $notificationData->title,
                    'project' => [
                        'id' => $notificationData->project->id,
                        'title' => $notificationData->project->title
                    ],
                    'created_by' => $notificationData->created_by,
                    'history' => $historyArray,
                ];
                Notification::send($user, new ProjectNotification($notificationBody, $broadcastMessage));
                break;
        }
        */
    }

    /**
     * this function creates room notifications
     * @param User $notificationTo
     * @param String $title
     * @param array|null $description
     * @param NotificationConstEnum|null $notificationConstEnum
     * @param String $icon
     * @param array $buttons
     * @param bool $showHistory
     * @param String $historyType
     * @param int|null $modelId
     * @param array|null $broadcastMessage
     * @param int|null $roomId
     * @param int|null $eventId
     * @param int|null $projectId
     * @param int|null $departmentId
     * @param int|null $taskId
     * @return void
     */
    public function createNotification(): void
    {
        $body = new \stdClass();
        $body->icon = $this->getIcon();
        $body->groupType = $this->getNotificationConstEnum()->groupType();
        $body->type = $this->getNotificationConstEnum();
        $body->description = $this->getDescription();
        $body->title = $this->getTitle();
        $body->buttons = $this->getButtons();
        $body->showHistory = $this->isShowHistory();
        $body->historyType = $this->getHistoryType();
        $body->modelId = $this->getModelId();
        $body->roomId = $this->getRoomId();
        $body->eventId = $this->getEventId();
        $body->projectId = $this->getProjectId();
        $body->departmentId = $this->departmentId;
        $body->taskId = $this->getTaskId();
        $body->created_by = Auth::user() ? Auth::user()->withoutRelations() : null;
        $body->created_at = Carbon::now()->translatedFormat('d.m.Y H:i');
        $body->budgetData = $this->getBudgetData();
        $body->notificationKey = $this->getNotificationKey();
        switch ($this->getNotificationConstEnum()) {
            case NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
            case NotificationConstEnum::NOTIFICATION_ROOM_REQUEST:
            case NotificationConstEnum::NOTIFICATION_ROOM_ANSWER:
                Notification::send($this->getNotificationTo(), new RoomRequestNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_EVENT_CHANGED:
                Notification::send($this->getNotificationTo(), new EventNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_NEW_TASK:
            case NotificationConstEnum::NOTIFICATION_TASK_CHANGED:
                Notification::send($this->getNotificationTo(), new TaskNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_PROJECT:
            case NotificationConstEnum::NOTIFICATION_PUBLIC_RELEVANT:
                Notification::send($this->getNotificationTo(), new ProjectNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_TEAM:
                Notification::send($this->getNotificationTo(), new TeamNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_ROOM_CHANGED:
                Notification::send($this->getNotificationTo(), new RoomNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                Notification::send($this->getNotificationTo(), new ConflictNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_TASK_REMINDER:
                Notification::send($this->getNotificationTo(), new DeadlineNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED:
                Notification::send($this->getNotificationTo(), new MoneySourceNotification($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
            case NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED:
                Notification::send($this->getNotificationTo(), new BudgetVerified($body, $this->getBroadcastMessage()));
                break;
            case NotificationConstEnum::NOTIFICATION_SHIFT_LOCKED:
            case NotificationConstEnum::NOTIFICATION_SHIFT_AVAILABLE:
            case NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED:
            case NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT:
            case NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT:
            case NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT:
                Notification::send($this->getNotificationTo(), new ShiftNotification($body, $this->getBroadcastMessage()));
                /*
            case NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED:
                throw new \Exception('To be implemented');
                break;
            case NotificationConstEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED:
                throw new \Exception('To be implemented');
                break;
            case NotificationConstEnum::NOTIFICATION_REMINDER_ROOM_REQUEST:
                throw new \Exception('To be implemented');
                */
        }
    }

    public function deleteBudgetNotification(){

    }

}
