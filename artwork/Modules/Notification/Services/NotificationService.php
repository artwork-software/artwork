<?php

namespace Artwork\Modules\Notification\Services;

use Illuminate\Notifications\Notification as LaravelNotification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Events\NewNotificationBroadcast;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Notification;
use stdClass;

class NotificationService
{
    public ?User $notificationTo = null;

    public ?NotificationEnum $notificationConstEnum = null;

    public string $title = '';

    public array|null $description = [];

    public string $icon = 'gray';

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

    public int|null $positionVerifyRequestId = null;

    public string|null $positionVerifyRequestType = null;

    public ?User $createdBy = null;

    public function __construct(
        private readonly EventService $eventService,
        private readonly UserService $userService,
    ) {
    }

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

    public function getNotificationTo(): ?User
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

    public function getEventByEventId(): ?Event
    {
        return $this->eventId ? $this->eventService->findEventById($this->eventId) : null;
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

    public function getPositionVerifyRequestId(): ?int
    {
        return $this->positionVerifyRequestId;
    }

    public function setPositionVerifyRequestId(?int $positionVerifyRequestId): self
    {
        $this->positionVerifyRequestId = $positionVerifyRequestId;

        return $this;
    }

    public function getPositionVerifyRequestType(): ?string
    {
        return $this->positionVerifyRequestType;
    }

    public function setPositionVerifyRequestType(?string $positionVerifyRequestType): self
    {
        $this->positionVerifyRequestType = $positionVerifyRequestType;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * Farbe des Benachrichtigungs-Icons; nur Varianten mit SVG (public/Svgs/IconSvgs).
     */
    private function displayIcon(): string
    {
        return match ($this->getIcon()) {
            'red', 'warning' => 'red',
            'green' => 'green',
            'blue', 'workflow' => 'blue',
            default => 'gray',
        };
    }

    public function clearNotificationData(): void
    {
        $this->setTitle('');
        $this->setDescription([]);
        $this->setNotificationConstEnum(null);
        $this->setIcon('gray');
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
        $this->setPositionVerifyRequestId(null);
        $this->setPositionVerifyRequestType(null);
        $this->setCreatedBy(null);
        $this->setPriority(0);
        $this->notificationTo = null;
    }

    /**
     * Handelnde interne Nutzer*in. Im externen Gastkontext (Default-Guard „external“) liefert Auth::user()
     * das ExternalAccess-Modell — dessen ID darf nie mit einer User-ID verglichen oder als created_by
     * gespeichert werden.
     */
    private function actingUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Nur, was die Kopfzeile braucht. Vorher landete das ganze User-Modell (E-Mail, Telefon,
     * Stundenkonto, Login-IDs …) in notifications.data und ging so an alle Empfänger*innen;
     * Kontaktdaten lädt der Tooltip rechtegeprüft nach (user.tooltip.info).
     *
     * @return array{id: int, first_name: ?string, last_name: ?string, profile_photo_url: ?string}|null
     */
    private function creatorSummary(?User $creator): ?array
    {
        if ($creator === null) {
            return null;
        }

        return [
            'id' => $creator->id,
            'first_name' => $creator->first_name,
            'last_name' => $creator->last_name,
            'profile_photo_url' => $creator->profile_photo_url,
        ];
    }

    private function actingUserId(): ?int
    {
        return $this->actingUser()?->id;
    }
    public function createNotification(): void
    {
        if (!$this->getNotificationTo()) {
            return;
        }

        $body = new stdClass();
        // vorher fest 'gray' – die gesetzte Statusfarbe kam nie an
        $body->icon = $this->displayIcon();
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
        $body->event = $this->getEventByEventId();
        $body->projectId = $this->getProjectId();
        $body->departmentId = $this->departmentId;
        $body->taskId = $this->getTaskId();
        $body->created_by = $this->creatorSummary($this->createdBy ?? $this->actingUser());
        $body->created_at = Carbon::now()->translatedFormat('d.m.Y H:i');
        $body->budgetData = $this->getBudgetData();
        $body->notificationKey = $this->getNotificationKey();
        $body->shiftId = $this->getShiftId();
        $body->positionVerifyRequestId = $this->getPositionVerifyRequestId();
        $body->positionVerifyRequestType = $this->getPositionVerifyRequestType();

        $type = $this->getNotificationConstEnum();
        $notificationClass = $type->notificationClass();
        // Handelnd ist auch die Person, die eine geplante Änderung ausgelöst hat (Scheduler ohne Auth) –
        // sonst bekam sie ihre eigenen Projekt-/Termin-/Aufgabenänderungen gemeldet
        $actingUserId = $this->createdBy?->id ?? $this->actingUserId();
        $isDelivered = $notificationClass !== null &&
            ($type->notifiesActingUser() || $this->getNotificationTo()->id !== $actingUserId);

        if ($isDelivered) {
            $this->sendNotification(
                $this->getNotificationTo(),
                new $notificationClass($body, $this->getBroadcastMessage() ?? [])
            );
        }

        // Live-Toast bewusst auch für die handelnde Person (Rückmeldung, z. B. Planer-Warnungen);
        // die Glocke nur, wenn wirklich ein Eintrag im Benachrichtigungscenter entstanden ist
        $this->broadcastLiveHint($this->getNotificationTo(), $type, $this->getBroadcastMessage() ?? []);
        if ($isDelivered) {
            $this->userService->updateCurrentUserShowNotificationIndicator($this->getNotificationTo(), true);
        }
    }

    /**
     * Zugestellte Benachrichtigung, die nicht über createNotification läuft (z. B.
     * Schichtregel-Verstöße): Live-Toast gemäß Einstellung „Push“ plus Glocke.
     *
     * @param array<string, mixed> $broadcastMessage
     */
    public function pushToUser(User $user, NotificationEnum $type, array $broadcastMessage): void
    {
        $this->broadcastLiveHint($user, $type, $broadcastMessage);
        $this->userService->updateCurrentUserShowNotificationIndicator($user, true);
    }

    /**
     * @param array<string, mixed> $broadcastMessage
     */
    private function broadcastLiveHint(User $user, NotificationEnum $type, array $broadcastMessage): void
    {
        if ($broadcastMessage === []) {
            return;
        }

        $pushEnabled = (bool) $user->notificationSettings()->where('type', $type->value)->value('enabled_push');
        if (!$pushEnabled) {
            return;
        }

        // Wie bei den Sofort-Mails: ist der WebSocket-Server nicht erreichbar, fällt nur der
        // Live-Hinweis aus – nicht die bereits gespeicherte Aktion (vorher 500 nach dem Speichern)
        try {
            broadcast(new NewNotificationBroadcast($user, $broadcastMessage));
        } catch (BroadcastException $exception) {
            report($exception);
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
        // Fallback auf start_date/end_date: Carbon::parse(null . ' ' . $start) ergäbe
        // das heutige Datum und macht den Ruhezeit-Check für solche Schichten unbrauchbar.
        $shiftStartDay = $shift->event_start_day
            ?? ($shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null);
        $shiftEndDay = $shift->event_end_day
            ?? ($shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : $shiftStartDay);
        $newShiftStart = Carbon::parse($shiftStartDay . ' ' . $shift->start);
        $newShiftEnd = Carbon::parse($shiftEndDay . ' ' . $shift->end);

        // If the shift crosses midnight (end time < start time on same day), advance end by 1 day
        if ($newShiftEnd->lessThanOrEqualTo($newShiftStart)) {
            $newShiftEnd->addDay();
        }

        $otherShifts = $user->shifts()
            ->where('shifts.id', '!=', $shift->id)
            ->whereBetween(
                'event_start_day',
                [
                    Carbon::parse($shiftStartDay)->subDay(),
                    Carbon::parse($shiftStartDay)->addDay()
                ]
            )
            ->without(['craft'])
            ->get();

        $notificationObj = new stdClass();
        $notificationObj->shortBreak = false;

        foreach ($otherShifts as $otherShift) {
            $otherStartDay = $otherShift->event_start_day
                ?? ($otherShift->start_date ? Carbon::parse($otherShift->start_date)->toDateString() : null);
            $otherEndDay = $otherShift->event_end_day
                ?? ($otherShift->end_date ? Carbon::parse($otherShift->end_date)->toDateString() : $otherStartDay);
            $otherStart = Carbon::parse($otherStartDay . ' ' . $otherShift->start);
            $otherEnd = Carbon::parse($otherEndDay . ' ' . $otherShift->end);

            // If the other shift crosses midnight, advance end by 1 day
            if ($otherEnd->lessThanOrEqualTo($otherStart)) {
                $otherEnd->addDay();
            }

            // Rest period = gap between consecutive shifts (the smaller of the two gaps)
            $restAfterNew = abs($otherStart->diffInRealHours($newShiftEnd));
            $restAfterOther = abs($newShiftStart->diffInRealHours($otherEnd));
            $restHours = min($restAfterNew, $restAfterOther);

            if ($restHours < $minDurationHours) {
                $notificationObj->shortBreak = true;
                $notificationObj->firstShift = $otherShift;
                $notificationObj->lastShift = $shift;
            }
        }

        return $notificationObj;
    }

    public function updateExistingRoomRequestNotification(
        int $eventId,
        int $recipientUserId,
        array $newDescription
    ): bool {
        $existingNotification = DB::table('notifications')
            ->where('data->type', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value)
            ->where('data->eventId', $eventId)
            ->where('notifiable_id', $recipientUserId)
            ->whereNull('data->handledStatus')
            ->first();

        if (!$existingNotification) {
            return false;
        }

        $data = json_decode($existingNotification->data, true);
        $data['isModified'] = true;
        $data['modifiedAt'] = now()->translatedFormat('d.m.Y H:i');
        $data['modifiedCount'] = ($data['modifiedCount'] ?? 0) + 1;
        $data['description'] = $newDescription;

        DB::table('notifications')
            ->where('id', $existingNotification->id)
            ->update([
                'data' => json_encode($data),
                'updated_at' => now(),
                'read_at' => null,
            ]);

        return true;
    }

    public function updateRoomRequestNotificationStatus(int $eventId, string $status, ?User $handledBy = null): void
    {
        $notifications = DB::table('notifications')
            ->where('data->type', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value)
            ->where('data->eventId', $eventId)
            ->get();

        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);
            $data['handledStatus'] = $status;
            $data['handledBy'] = $handledBy
                ? ['id' => $handledBy->id, 'name' => $handledBy->display_name]
                : null;
            $data['handledAt'] = now()->translatedFormat('d.m.Y H:i');
            $data['buttons'] = [];
            DB::table('notifications')
                ->where('id', $notification->id)
                ->update(['data' => json_encode($data)]);
        }
    }

    public function deleteUnhandledRoomRequestNotificationsByEventId(int $eventId): void
    {
        DB::table('notifications')
            ->where('data->type', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value)
            ->where('data->eventId', $eventId)
            ->whereNull('data->handledStatus')
            ->delete();
    }

    public function deleteUpsertRoomRequestNotificationByEventId(int $eventId): void
    {
        DB::table('notifications')
            ->where('type', RoomRequestNotification::class)
            ->where('data->type', NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST->value)
            ->where('data->eventId', $eventId)
            ->delete();
    }

    /**
     * Sofort-Mails laufen synchron im Request. Ist der Mailserver nicht
     * erreichbar, soll nur die Mail ausfallen und gemeldet werden – nicht die Aktion,
     * die die Benachrichtigung ausgelöst hat (Termin speichern, Raumanfrage …).
     */
    private function sendNotification(User $notifiable, LaravelNotification $notification): void
    {
        try {
            Notification::send($notifiable, $notification);
        } catch (TransportExceptionInterface $exception) {
            report($exception);
        }
    }
}
