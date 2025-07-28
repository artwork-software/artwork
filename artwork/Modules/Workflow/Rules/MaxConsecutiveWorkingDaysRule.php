<?php

namespace Artwork\Modules\Workflow\Rules;

use Artwork\Modules\Workflow\Contracts\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MaxConsecutiveWorkingDaysRule implements WorkflowRule
{
    public function getName(): string
    {
        return 'max_consecutive_working_days';
    }

    public function getDescription(): string
    {
        return 'Überprüft die maximale Anzahl aufeinanderfolgender Arbeitstage';
    }

    public function validate(Model $subject, array $context = []): array
    {
        $violations = [];
        $maxDays = $context['value'] ?? 5;
        $startDate = $context['start_date'] ?? now()->subDays(7);
        $endDate = $context['end_date'] ?? now()->addDays(14);
        
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $consecutiveDays = 0;
        
        foreach ($dateRange as $date) {
            $plannedHours = $this->getPlannedWorkingHours($subject, $date);
            
            if ($plannedHours > 0) {
                $consecutiveDays++;
            } else {
                $consecutiveDays = 0;
            }
            
            if ($consecutiveDays > $maxDays) {
                $violations[] = [
                    'date' => $date->toDateString(),
                    'consecutive_days' => $consecutiveDays,
                    'max_days' => $maxDays,
                    'planned_hours' => $plannedHours,
                    'severity' => 'high',
                    'message' => "Zu viele aufeinanderfolgende Arbeitstage ({$consecutiveDays}/{$maxDays})"
                ];
            }
        }
        
        return $violations;
    }

    public function canApplyTo(Model $subject): bool
    {
        return method_exists($subject, 'getPlannedWorkingHours') || 
               method_exists($subject, 'shifts');
    }

    public function getConfiguration(): array
    {
        return [
            'fields' => [
                'max_days' => [
                    'type' => 'number',
                    'label' => 'Maximale aufeinanderfolgende Arbeitstage',
                    'default' => 5,
                    'min' => 1,
                    'max' => 14
                ]
            ]
        ];
    }

    private function getPlannedWorkingHours(Model $subject, Carbon $date): float
    {
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