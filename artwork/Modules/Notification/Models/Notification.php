<?php

namespace Artwork\Modules\Notification\Models;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;

class Notification
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

    public function getNotificationTo(): ?User
    {
        return $this->notificationTo;
    }

    public function setNotificationTo(?User $notificationTo): void
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
     * @return array<mixed, mixed>|null
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
     * @return array<mixed, mixed>
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
     * @return array<mixed, mixed>|null
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

    public function getShiftId(): ?int
    {
        return $this->shiftId;
    }

    public function setShiftId(?int $shiftId): void
    {
        $this->shiftId = $shiftId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
}
