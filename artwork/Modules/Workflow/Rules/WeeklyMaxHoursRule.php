<?php

namespace Artwork\Modules\Workflow\Rules;

use Artwork\Modules\Workflow\Contracts\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WeeklyMaxHoursRule implements WorkflowRule
{
    public function getName(): string
    {
        return 'weekly_max_hours';
    }

    public function getDescription(): string
    {
        return 'Überprüft das Wochenmaximum an Arbeitsstunden';
    }

    public function validate(Model $subject, array $context = []): array
    {
        $violations = [];
        $maxHours = $context['value'] ?? 40;
        $startDate = $context['start_date'] ?? now()->subDays(7);
        $endDate = $context['end_date'] ?? now()->addDays(14);
        
        // Erweitere Datumsbereich auf vollständige Wochen (Montag bis Sonntag)
        $adjustedRange = $this->adjustToFullWeeks($startDate, $endDate);
        $dateRange = CarbonPeriod::create($adjustedRange['start'], $adjustedRange['end']);
        
        $plannedWorkingHoursOfWeek = 0;
        
        foreach ($dateRange as $date) {
            // Reset bei neuem Montag
            if ($date->isMonday()) {
                $plannedWorkingHoursOfWeek = 0;
            }
            
            $dailyHours = $this->getPlannedWorkingHours($subject, $date);
            $plannedWorkingHoursOfWeek += $dailyHours;
            
            // Prüfe Überschreitung
            if ($plannedWorkingHoursOfWeek > $maxHours) {
                $violations[] = [
                    'date' => $date->toDateString(),
                    'weekly_hours' => $plannedWorkingHoursOfWeek,
                    'max_hours' => $maxHours,
                    'daily_hours' => $dailyHours,
                    'week_start' => $date->startOfWeek()->toDateString(),
                    'severity' => 'high',
                    'message' => "Wochenmaximum von {$maxHours}h überschritten ({$plannedWorkingHoursOfWeek}h)"
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
                'max_hours' => [
                    'type' => 'number',
                    'label' => 'Maximale Stunden pro Woche',
                    'default' => 40,
                    'min' => 1,
                    'max' => 80
                ]
            ]
        ];
    }

    private function adjustToFullWeeks(Carbon $startDate, Carbon $endDate): array
    {
        // Erweitere Start auf vorherigen Montag falls nötig
        $adjustedStart = $startDate->copy();
        if (!$adjustedStart->isMonday()) {
            $adjustedStart = $adjustedStart->startOfWeek();
        }
        
        // Erweitere Ende auf nächsten Sonntag falls nötig
        $adjustedEnd = $endDate->copy();
        if (!$adjustedEnd->isSunday()) {
            $adjustedEnd = $adjustedEnd->endOfWeek();
        }
        
        return [
            'start' => $adjustedStart,
            'end' => $adjustedEnd
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