<?php

namespace Artwork\Modules\Workflow\Rules;

use Artwork\Modules\Workflow\Contracts\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaxWorkingHoursOnDayRule implements WorkflowRule
{
    public function getName(): string
    {
        return 'max_working_hours_on_day';
    }

    public function getDescription(): string
    {
        return 'Überprüft das Tagesmaximum an Arbeitsstunden';
    }

    public function validate(Model $subject, array $context = []): array
    {
        $violations = [];
        $date = $context['date'] ?? now();
        $maxHours = $context['max_hours'] ?? 8;
        
        // Get planned working hours for the subject on the given date
        $plannedHours = $this->getPlannedWorkingHours($subject, $date);
        
        if ($plannedHours > $maxHours) {
            $violations[] = [
                'date' => $date,
                'planned_hours' => $plannedHours,
                'max_hours' => $maxHours,
                'message' => "Tagesmaximum von {$maxHours}h überschritten ({$plannedHours}h geplant)"
            ];
        }
        
        return $violations;
    }

    public function canApplyTo(Model $subject): bool
    {
        // This rule can apply to any model that has a method to get working hours
        return method_exists($subject, 'getPlannedWorkingHours') || 
               method_exists($subject, 'shifts');
    }

    public function getConfiguration(): array
    {
        return [
            'fields' => [
                'max_hours' => [
                    'type' => 'number',
                    'label' => 'Maximale Stunden pro Tag',
                    'default' => 8,
                    'min' => 1,
                    'max' => 24
                ]
            ]
        ];
    }

    private function getPlannedWorkingHours(Model $subject, Carbon $date): float
    {
        // Implementation depends on the subject model
        // This is a placeholder - actual implementation would depend on 
        // how working hours are stored (shifts, events, etc.)
        
        if (method_exists($subject, 'getPlannedWorkingHours')) {
            return $subject->getPlannedWorkingHours($date);
        }
        
        if (method_exists($subject, 'shifts')) {
            return $subject->shifts()
                ->whereDate('start_time', $date)
                ->get()
                ->sum(function ($shift) {
                    return $shift->duration_hours ?? 0;
                });
        }
        
        return 0;
    }
}
