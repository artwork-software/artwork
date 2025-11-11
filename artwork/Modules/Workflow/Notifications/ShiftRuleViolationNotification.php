<?php

namespace Artwork\Modules\Workflow\Notifications;

use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftRuleViolationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftRuleViolation $violation;
    protected string $message;

    public function __construct(ShiftRuleViolation $violation, string $message)
    {
        $this->violation = $violation;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $rule = $this->violation->shiftRule;
        
        return (new MailMessage)
            ->subject('Schicht-Regelverstoß erkannt')
            ->greeting('Hallo ' . $notifiable->first_name . ',')
            ->line($this->message)
            ->line('Regel: ' . $rule->name)
            ->line('Datum: ' . $this->violation->violation_date->format('d.m.Y'))
            ->action('Relevante Schichten anzeigen', $this->getShiftPlanUrl())
            ->line('Bitte überprüfen Sie den Schichtplan und nehmen Sie gegebenenfalls Anpassungen vor.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'shift_rule_violation',
            'violation_id' => $this->violation->id,
            'rule_name' => $this->violation->shiftRule->name,
            'violation_date' => $this->violation->violation_date->format('Y-m-d'),
            'message' => $this->message,
            'severity' => $this->violation->severity,
            'warning_color' => $this->violation->shiftRule->warning_color,
            'shift_plan_url' => $this->getShiftPlanUrl()
        ];
    }

    private function getShiftPlanUrl(): string
    {
        $violationDate = $this->violation->violation_date;
        $startDate = $violationDate instanceof Carbon ? $violationDate : Carbon::parse($violationDate);
        $endDate = $startDate->copy()->addDays(7);
        
        // Generiere URL zum Schichtplan mit dem relevanten Datumsbereich
        return route('shift-plan.index', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);
    }
}