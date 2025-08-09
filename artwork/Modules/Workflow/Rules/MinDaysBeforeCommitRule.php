<?php

namespace Artwork\Modules\Workflow\Rules;

use Artwork\Modules\Workflow\Contracts\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MinDaysBeforeCommitRule implements WorkflowRule
{
    public function getName(): string
    {
        return 'min_days_before_commit';
    }

    public function getDescription(): string
    {
        return 'Überprüft die Mindesttage bis zur Verbindlich-Schaltung einer Schicht';
    }

    public function validate(Model $subject, array $context = []): array
    {
        $violations = [];
        $minDays = $context['value'] ?? 14;
        $today = now();
        $checkUntilDate = $today->copy()->addDays($minDays);
        
        // Hole alle nicht-verbindlichen Schichten im Zeitraum
        $uncommittedShifts = $this->getUncommittedShifts($checkUntilDate);
        
        if ($uncommittedShifts->isNotEmpty()) {
            $violations[] = [
                'date' => $today->toDateString(),
                'uncommitted_shifts_count' => $uncommittedShifts->count(),
                'min_days' => $minDays,
                'check_until_date' => $checkUntilDate->toDateString(),
                'shifts' => $uncommittedShifts->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'start_time' => $shift->start_time,
                        'end_time' => $shift->end_time,
                        'event_name' => $shift->event->name ?? 'Unbekannt',
                        'days_until_shift' => now()->diffInDays(Carbon::parse($shift->start_time))
                    ];
                })->toArray(),
                'severity' => 'medium',
                'message' => "Es gibt {$uncommittedShifts->count()} nicht-verbindliche Schichten in den nächsten {$minDays} Tagen"
            ];
        }
        
        return $violations;
    }

    public function canApplyTo(Model $subject): bool
    {
        // Diese Regel ist nicht benutzerbezogen, sondern systemweit
        return true;
    }

    public function getConfiguration(): array
    {
        return [
            'fields' => [
                'min_days' => [
                    'type' => 'number',
                    'label' => 'Mindesttage bis zur Verbindlich-Schaltung',
                    'default' => 14,
                    'min' => 1,
                    'max' => 30
                ]
            ],
            'global_rule' => true, // Markiert diese Regel als systemweit
            'notification_required' => true // Diese Regel erfordert Benachrichtigungen
        ];
    }

    private function getUncommittedShifts(Carbon $checkUntilDate)
    {
        // Diese Implementierung hängt vom Shift-Model ab
        // Annahme: Shift-Model hat 'is_committed' Boolean-Feld
        
        $shiftClass = \Artwork\Modules\Shift\Models\Shift::class;
        
        if (!class_exists($shiftClass)) {
            return collect();
        }
        
        return $shiftClass::where('is_committed', false)
            ->whereBetween('start_time', [now(), $checkUntilDate])
            ->with(['event']) // Lade Event-Relation für bessere Info
            ->get();
    }

    public function getRelevantShiftsForViolation(array $violationData): array
    {
        if (!isset($violationData['shifts'])) {
            return [];
        }
        
        return $violationData['shifts'];
    }

    public function generateNotificationMessage(array $violationData): string
    {
        $count = $violationData['uncommitted_shifts_count'] ?? 0;
        $minDays = $violationData['min_days'] ?? 14;
        
        return "Achtung: Es gibt {$count} nicht-verbindliche Schichten in den nächsten {$minDays} Tagen, die verbindlich geschaltet werden müssen.";
    }
}