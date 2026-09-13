<?php

namespace Artwork\Modules\Workflow\Notifications;

use Artwork\Core\Notifications\BaseNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;
use stdClass;

/**
 * Erbt von BaseNotification, damit via() die Kanaleinstellungen der Person
 * (E-Mail/Push je Benachrichtigungstyp) respektiert; Typ = Regelverstoß bei
 * der Planung anderer (NOTIFICATION_SHIFT_INFRINGEMENT).
 */
class ShiftRuleViolationNotification extends BaseNotification implements ShouldQueue
{
    protected ShiftRuleViolation $violation;
    protected string $message;

    public function __construct(ShiftRuleViolation $violation, string $message)
    {
        $this->violation = $violation;
        $this->message = $message;

        parent::__construct($this->buildNotificationData(), [
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $message,
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        // Queued Notification: zwischen Enqueue und Versand kann die Regel gelöscht
        // worden sein (Violations werden bei Re-Checks aufgeräumt) — ohne Guard
        // crasht der Queue-Job und die Mail geht verloren.
        $rule = $this->violation->shiftRule;
        $language = $notifiable->language ?? null;

        return (new MailMessage())
            ->subject(__('Shift rule violation detected', [], $language))
            ->greeting(__('Hello :name,', ['name' => $notifiable->first_name], $language))
            ->line($this->message)
            ->line(__('Rule', [], $language) . ': ' . ($rule?->name ?? __('Deleted rule', [], $language)))
            ->line(__('Date', [], $language) . ': ' . $this->violation->violation_date?->format('d.m.Y'))
            ->action(__('Show relevant shifts', [], $language), $this->getShiftPlanUrl())
            ->line(__('Please check the shift plan and adjust it if necessary.', [], $language));
    }

    public function toArray(): stdClass
    {
        $data = parent::toArray();
        $data->violation_id = $this->violation->id;
        $data->rule_name = $this->violation->shiftRule?->name ?? __('Deleted rule');
        $data->violation_date = $this->violation->violation_date?->format('Y-m-d');
        $data->message = $this->message;
        $data->severity = $this->violation->severity;
        $data->warning_color = $this->violation->shiftRule?->warning_color;
        $data->shift_plan_url = $this->getShiftPlanUrl();

        return $data;
    }

    /**
     * Link in den Schichtplan (Route shifts.plan) auf die Woche ab dem Verstoßdatum.
     */
    public function getShiftPlanUrl(): string
    {
        $violationDate = $this->violation->violation_date;
        $startDate = $violationDate instanceof Carbon ? $violationDate->copy() : Carbon::parse($violationDate ?? 'now');
        $endDate = $startDate->copy()->addDays(6);

        return route('shifts.plan', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
    }

    private function buildNotificationData(): stdClass
    {
        $type = NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT;
        $ruleName = $this->violation->shiftRule?->name ?? $this->violation->title ?? __('Deleted rule');

        $body = new stdClass();
        $body->icon = 'red';
        $body->priority = 2;
        $body->groupType = $type->groupType();
        $body->type = $type;
        $body->title = __('Shift rule violation detected') . ': ' . $ruleName;
        $body->description = [
            1 => [
                'type' => 'string',
                'title' => $this->message,
                'href' => $this->getShiftPlanUrl(),
            ],
        ];
        $body->buttons = [];
        $body->showHistory = false;
        $body->historyType = null;
        $body->modelId = $this->violation->id;
        $body->roomId = null;
        $body->eventId = null;
        $body->event = null;
        $body->projectId = null;
        $body->departmentId = null;
        $body->taskId = null;
        $body->created_by = null;
        $body->created_at = Carbon::now()->translatedFormat('d.m.Y H:i');
        $body->budgetData = null;
        $body->notificationKey = '';
        $body->shiftId = $this->violation->shift_id;
        $body->positionVerifyRequestId = null;
        $body->positionVerifyRequestType = null;

        return $body;
    }
}
