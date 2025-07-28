<?php

namespace Artwork\Modules\Workflow\Rules;

use Artwork\Modules\Workflow\Contracts\WorkflowRule;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RestTimeBeforeHolidayRule implements WorkflowRule
{
    public function getName(): string
    {
        return 'rest_time_before_holiday';
    }

    public function getDescription(): string
    {
        return 'Überprüft die Ruhezeit vor Sonder-/Sonntagen';
    }

    public function validate(Model $subject, array $context = []): array
    {
        $violations = [];
        $minRestHours = $context['value'] ?? 12;
        $startDate = $context['start_date'] ?? now()->subDays(1);
        $endDate = $context['end_date'] ?? now()->addDays(14);
        
        // Erweitere Datumsbereich um einen Tag nach vorne für Vergleich
        $extendedStart = $startDate->copy()->subDay();
        $dateRange = CarbonPeriod::create($extendedStart, $endDate);
        
        $latestShiftEndLastDay = null;
        
        foreach ($dateRange as $date) {
            // Prüfe nur Sonn-/Feiertage
            if (!$this->isHoliday($date)) {
                $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($subject, $date);
                continue;
            }
            
            $earliestShiftStart = $this->getEarliestShiftStartOfDay($subject, $date);
            
            // Prüfe Ruhezeit wenn beide Werte vorhanden sind
            if ($latestShiftEndLastDay && $earliestShiftStart) {
                $restHours = $this->calculateRestHours($latestShiftEndLastDay, $earliestShiftStart);
                
                if ($restHours < $minRestHours) {
                    $violations[] = [
                        'date' => $date->toDateString(),
                        'rest_hours' => $restHours,
                        'min_rest_hours' => $minRestHours,
                        'last_shift_end' => $latestShiftEndLastDay->toDateTimeString(),
                        'next_shift_start' => $earliestShiftStart->toDateTimeString(),
                        'severity' => 'medium',
                        'message' => "Zu wenig Ruhezeit vor Sonn-/Feiertag ({$restHours}h statt {$minRestHours}h)"
                    ];
                }
            }
            
            // Aktualisiere letztes Schichtende für nächsten Tag
            $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($subject, $date);
        }
        
        return $violations;
    }

    public function canApplyTo(Model $subject): bool
    {
        return method_exists($subject, 'shifts');
    }

    public function getConfiguration(): array
    {
        return [
            'fields' => [
                'min_rest_hours' => [
                    'type' => 'number',
                    'label' => 'Mindest-Ruhezeit vor Sonn-/Feiertag (Stunden)',
                    'default' => 12,
                    'min' => 1,
                    'max' => 48
                ]
            ]
        ];
    }

    private function isHoliday(Carbon $date): bool
    {
        // Sonntag ist immer Feiertag
        if ($date->isSunday()) {
            return true;
        }
        
        // Hier könnte eine Feiertag-Prüfung gegen die Holiday-Tabelle stehen
        // Beispiel:
        // return Holiday::whereDate('date', $date)->exists();
        
        return false;
    }

    private function getEarliestShiftStartOfDay(Model $subject, Carbon $date): ?Carbon
    {
        if (!method_exists($subject, 'shifts')) {
            return null;
        }
        
        $shift = $subject->shifts()
            ->whereDate('start_time', $date)
            ->orderBy('start_time', 'asc')
            ->first();
            
        return $shift ? Carbon::parse($shift->start_time) : null;
    }

    private function getLatestShiftEndOfDay(Model $subject, Carbon $date): ?Carbon
    {
        if (!method_exists($subject, 'shifts')) {
            return null;
        }
        
        $shift = $subject->shifts()
            ->whereDate('end_time', $date)
            ->orderBy('end_time', 'desc')
            ->first();
            
        return $shift ? Carbon::parse($shift->end_time) : null;
    }

    private function calculateRestHours(Carbon $shiftEnd, Carbon $nextShiftStart): float
    {
        $diffInHours = $nextShiftStart->diffInHours($shiftEnd, false);
        
        // Handle cases where rest time spans more than 24h
        if ($diffInHours < 0) {
            $diffInHours += 24;
        }
        
        return $diffInHours;
    }
}