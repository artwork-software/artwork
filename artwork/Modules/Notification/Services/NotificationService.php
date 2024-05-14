<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Budget\Notifications\BudgetVerified;
use Artwork\Modules\Department\Notifications\TeamNotification;
use Artwork\Modules\Event\Notifications\ConflictNotification;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\MoneySource\Notifications\MoneySourceNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\Room\Notifications\RoomNotification;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Task\Notifications\DeadlineNotification;
use Artwork\Modules\Task\Notifications\TaskNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use stdClass;

class NotificationService
{
    public ?User $notificationTo = null;

    public string $title;

    public array|null $description = [];

    public ?NotificationEnum $notificationConstEnum = null;

    public string $icon = 'green';

    public array $buttons = [];

    public bool $showHistory = false;

    public string $historyType = '';

    public int|null $modelId = null;

    public array|null $broadcastMessage = [];

    public int|null $roomId = null;

    public int|null $eventId = null;

    public int|null $projectId = null;

    public int|null $departmentId = null;

    public int|null $taskId = null;

    public int|null $shiftId = null;

    public int $priority = 0;

    protected string $notificationKey = '';

    public object|null $budgetData = null;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getShiftId(): ?int
    {
        return $this->shiftId;
    }

    public function setShiftId(?int $shiftId): void
    {
        $this->shiftId = $shiftId;
    }

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

    /**
     * @return null|array<int, array<string, mixed>>
     */
    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function setDescription(?array $description): void
    {
        $this->description = $description;
    }

    public function getNotificationConstEnum(): ?NotificationEnum
    {
        return $this->notificationConstEnum;
    }

    public function setNotificationConstEnum(?NotificationEnum $notificationConstEnum): void
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

    /**
     * @return string[]
     */
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

    /**
     * @return null|array<string,mixed>
     */
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

    public function getNotificationKey(): string
    {
        return $this->notificationKey;
    }

    public function setNotificationKey(string $notificationKey): void
    {
        $this->notificationKey = $notificationKey;
    }

    public function clearNotificationData(): void
    {
        $this->setTitle('');
        $this->setDescription([]);
        $this->setNotificationConstEnum(null);
        $this->setIcon('green');
        $this->setButtons([]);
        $this->setShowHistory(false);
        $this->setHistoryType('');
        $this->setModelId(null);
        $this->setBroadcastMessage([]);
        $this->setRoomId(null);
        $this->setEventId(null);
        $this->setProjectId(null);
        $this->setDepartmentId(null);
        $this->setTaskId(null);
        $this->setBudgetData(null);
        $this->setNotificationKey('');
        $this->setShiftId(null);
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function createNotification(): void
    {
        if (!$this->getNotificationTo()) {
            return;
        }
        $body = new stdClass();
        $body->icon = $this->getIcon();
        $body->priority = $this->getPriority();
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
        $body->shiftId = $this->getShiftId();
        switch ($this->getNotificationConstEnum()) {
            case NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST:
            case NotificationEnum::NOTIFICATION_ROOM_REQUEST:
            case NotificationEnum::NOTIFICATION_ROOM_ANSWER:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new RoomRequestNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_EVENT_CHANGED:
                if ($this->getNotificationTo() !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new EventNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_NEW_TASK:
            case NotificationEnum::NOTIFICATION_TASK_CHANGED:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new TaskNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_PROJECT:
            case NotificationEnum::NOTIFICATION_PUBLIC_RELEVANT:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new ProjectNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_TEAM:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new TeamNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_ROOM_CHANGED:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new RoomNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_CONFLICT:
            case NotificationEnum::NOTIFICATION_LOUD_ADJOINING_EVENT:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new ConflictNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_TASK_REMINDER:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new DeadlineNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED:
            case NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new MoneySourceNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_MONEY_SOURCE_EXPIRATION:
            case NotificationEnum::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED:
                Notification::send(
                    $this->getNotificationTo(),
                    new MoneySourceNotification($body, $this->getBroadcastMessage())
                );
                break;
            case NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED:
            case NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new BudgetVerified($body, $this->getBroadcastMessage())
                    );
                }
                break;
            case NotificationEnum::NOTIFICATION_SHIFT_LOCKED:
            case NotificationEnum::NOTIFICATION_SHIFT_AVAILABLE:
            case NotificationEnum::NOTIFICATION_SHIFT_CHANGED:
            case NotificationEnum::NOTIFICATION_SHIFT_CONFLICT:
            case NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT:
            case NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT:
            case NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND:
                if ($this->getNotificationTo()->id !== Auth::id()) {
                    Notification::send(
                        $this->getNotificationTo(),
                        new ShiftNotification($body, $this->getBroadcastMessage())
                    );
                }
                break;
        }
    }

    public function checkIfUserInMoreThanTenShifts(User $user, Shift $shift): stdClass
    {
        $shifts = $user->shifts()
            ->whereBetween(
                'event_start_day',
                [
                    Carbon::parse($shift->event_start_day)->subDays(10),
                    Carbon::parse($shift->event_start_day)->addDays(10)
                ]
            )
            ->get()->groupBy('event_start_day');

        $notificationObj = new stdClass();
        $notificationObj->moreThanTenShifts = false;

        if ($shifts->count() > 10) {
            $notificationObj->moreThanTenShifts = true;
            $notificationObj->firstShift = $shifts->first();
            $notificationObj->lastShift = $shifts->last();
        }

        return $notificationObj;
    }

    public function checkIfShortBreakBetweenTwoShifts(User $user, Shift $shift): stdClass
    {
        $minDurationHours = 12;
        $formattedEndTime = Carbon::parse($shift->event_end_day . ' ' . $shift->end);

        $shifts = $user->shifts()
            ->whereBetween(
                'event_start_day',
                [
                    Carbon::parse($shift->event_start_day)->subDay(),
                    Carbon::parse($shift->event_start_day)->addDay()
                ]
            )
            ->without(['craft'])
            ->get();

        $notificationObj = new stdClass();
        $notificationObj->shortBreak = false;

        foreach ($shifts as $shift) {
            $formattedStartTime = Carbon::parse($shift->event_start_day . ' ' . $shift->start);
            $diffInHours = $formattedStartTime->diffInRealHours($formattedEndTime);
            if ($diffInHours < $minDurationHours) {
                $notificationObj->shortBreak = true;
                $notificationObj->firstShift = $shifts->first();
                $notificationObj->lastShift = $shifts->last();
            }
        }

        return $notificationObj;
    }

    public function deleteUpsertRoomRequestNotificationByEventId(int $eventId): void
    {
        $notificationCollection = DB::table('notifications')
            ->where('type', EventNotification::class)
            ->where('data->type', NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST)
            ->where('data->eventId', $eventId)
            ->get('id');

        if ($notificationCollection->isNotEmpty()) {
            DB::table('notifications')->delete($notificationCollection->first()->id);
        }
    }
}
