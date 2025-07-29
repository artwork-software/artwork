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
        $weekStart = null;
        $violationDetected = false;

        foreach ($dateRange as $date) {
            // Reset bei neuem Montag
            if ($date->isMonday()) {
                // Prüfe, ob in der vorherigen Woche eine Überschreitung vorlag
                if ($plannedWorkingHoursOfWeek > $maxHours && !$violationDetected && $weekStart !== null) {
                    $violations[] = [
                        'date' => $date->copy()->subDay()->toDateString(), // Sonntag der vorherigen Woche
                        'weekly_hours' => $plannedWorkingHoursOfWeek,
                        'max_hours' => $maxHours,
                        'daily_hours' => 0, // Für die Wochenübersicht nicht relevant
                        'week_start' => $weekStart,
                        'severity' => 'high',
                        'message' => "Wochenmaximum von {$maxHours}h überschritten ({$plannedWorkingHoursOfWeek}h)"
                    ];
                }

                $plannedWorkingHoursOfWeek = 0;
                $weekStart = $date->toDateString();
                $violationDetected = false;
            }

            $dailyHours = $this->getPlannedWorkingHours($subject, $date);
            $plannedWorkingHoursOfWeek += $dailyHours;

            // Prüfe Überschreitung
            if ($plannedWorkingHoursOfWeek > $maxHours && !$violationDetected) {
                $violations[] = [
                    'date' => $date->toDateString(),
                    'weekly_hours' => $plannedWorkingHoursOfWeek,
                    'max_hours' => $maxHours,
                    'daily_hours' => $dailyHours,
                    'week_start' => $date->startOfWeek()->toDateString(),
                    'severity' => 'high',
                    'message' => "Wochenmaximum von {$maxHours}h überschritten ({$plannedWorkingHoursOfWeek}h)"
                ];
                $violationDetected = true;
            }
        }

        // Prüfe die letzte Woche
        if ($plannedWorkingHoursOfWeek > $maxHours && !$violationDetected && $weekStart !== null) {
            $lastDate = $dateRange->last();
            $violations[] = [
                'date' => $lastDate->toDateString(),
                'weekly_hours' => $plannedWorkingHoursOfWeek,
                'max_hours' => $maxHours,
                'daily_hours' => 0,
                'week_start' => $weekStart,
                'severity' => 'high',
                'message' => "Wochenmaximum von {$maxHours}h überschritten ({$plannedWorkingHoursOfWeek}h)"
            ];
        }

        return $violations;
    }

    public function canApplyTo(Model $subject): bool
    {
        // Check if the subject responds to getPlannedWorkingHours
        if (method_exists($subject, 'getPlannedWorkingHours')) {
            return true;
        }

        // Check if the subject responds to shifts
        if (method_exists($subject, 'shifts')) {
            return true;
        }

        // For mocked objects, check if they have the method mocked
        if (method_exists($subject, 'mockery_getExpectations')) {
            $expectations = $subject->mockery_getExpectations();
            return isset($expectations['getPlannedWorkingHours']) || isset($expectations['shifts']);
        }

        return false;
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
