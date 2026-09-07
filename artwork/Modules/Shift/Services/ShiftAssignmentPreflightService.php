<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Vorabprüfung VOR einer Schichtzuweisung (nur Warnung, kein Blocker):
 *  1. Zeitüberschneidung mit anderen Zuweisungen der Person (Pivot-Zeiten vor
 *     Schichtzeiten, Mitternachtsschichten),
 *  2. Urlaub / freier Tag (OFF_WORK, FREE_WORK) an einem Tag der Schicht,
 *  3. Nicht verfügbar (NOT_AVAILABLE) ganztags oder mit überlappendem Zeitfenster.
 *
 * Bewusst KEIN Regel-Check (zu teuer für den Drop-Pfad). Die eigentlichen
 * Konflikt-Datensätze legt weiterhin ShiftWorkerService::handleAssignedToShift an.
 */
class ShiftAssignmentPreflightService
{
    public const TYPE_OVERLAP = 'overlap';
    public const TYPE_VACATION = 'vacation';
    public const TYPE_UNAVAILABLE = 'unavailable';

    /**
     * @return array{
     *     conflicts: list<array{type: string, label: string, detail: string}>,
     *     person: array{name: string, type: string},
     *     shift: array{id: int, date: string, start: string, end: string, craft: string}
     * }
     */
    public function check(
        Shift $shift,
        User|Freelancer|ServiceProvider $worker,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $start = null,
        ?string $end = null,
    ): array {
        [$currentStart, $currentEnd] = self::resolveInterval(
            $startDate ?? $shift->start_date,
            $start ?? $shift->start,
            $endDate ?? $shift->end_date,
            $end ?? $shift->end,
        ) ?? [Carbon::now(), Carbon::now()];

        $conflicts = [
            ...$this->overlapConflicts($shift, $worker, $currentStart, $currentEnd),
            ...$this->absenceConflicts($worker, $currentStart, $currentEnd),
        ];

        return [
            'conflicts' => $conflicts,
            'person' => [
                'name' => $this->displayName($worker),
                'type' => self::typeKeyFor($worker),
            ],
            'shift' => [
                'id' => $shift->id,
                'date' => $currentStart->toDateString() === $currentEnd->toDateString()
                    ? $currentStart->format('d.m.Y')
                    : $currentStart->format('d.m.Y') . ' – ' . $currentEnd->format('d.m.Y'),
                'start' => $currentStart->format('H:i'),
                'end' => $currentEnd->format('H:i'),
                'craft' => $shift->craft?->name ?? '',
            ],
        ];
    }

    /**
     * Datum + Uhrzeit zu einem Intervall zusammensetzen; endet die Schicht vor/bei
     * ihrem Beginn (Mitternachtsschicht mit gleichem Datum), wird das Ende auf den
     * Folgetag gelegt. Gleiche Semantik wie ShiftController::checkCollisions.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function resolveInterval(
        mixed $startDate,
        mixed $startTime,
        mixed $endDate,
        mixed $endTime,
    ): ?array {
        if (!$startDate || !$startTime || !$endDate || !$endTime) {
            return null;
        }

        try {
            $startDateString = Carbon::parse($startDate)->toDateString();
            $endDateString = Carbon::parse($endDate)->toDateString();
            $intervalStart = Carbon::parse($startDateString . ' ' . Carbon::parse($startTime)->format('H:i'));
            $intervalEnd = Carbon::parse($endDateString . ' ' . Carbon::parse($endTime)->format('H:i'));
        } catch (\Throwable) {
            return null;
        }

        if ($intervalEnd->lessThanOrEqualTo($intervalStart)) {
            $intervalEnd->addDay();
        }

        return [$intervalStart, $intervalEnd];
    }

    /**
     * Effektives Intervall einer Zuweisung: individuelle Pivot-Zeiten haben Vorrang
     * vor den Schichtzeiten.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function resolvePivotInterval(ShiftWorker $pivot, Shift $shift): ?array
    {
        return self::resolveInterval(
            $pivot->start_date ?? $shift->start_date,
            $pivot->start_time ?? $shift->start,
            $pivot->end_date ?? $shift->end_date,
            $pivot->end_time ?? $shift->end,
        );
    }

    public static function intervalsOverlap(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lessThan($bEnd) && $aEnd->greaterThan($bStart);
    }

    public static function morphClassFor(string $typeKey): ?string
    {
        return match ($typeKey) {
            'user', '0' => User::class,
            'freelancer', '1' => Freelancer::class,
            'service_provider', '2' => ServiceProvider::class,
            default => null,
        };
    }

    public static function typeKeyFor(Model $worker): string
    {
        return match (true) {
            $worker instanceof User => 'user',
            $worker instanceof Freelancer => 'freelancer',
            default => 'service_provider',
        };
    }

    /**
     * @return list<array{type: string, label: string, detail: string}>
     */
    private function overlapConflicts(
        Shift $shift,
        Model $worker,
        Carbon $currentStart,
        Carbon $currentEnd,
    ): array {
        $scopeStart = $currentStart->copy()->subDay()->toDateString();
        $scopeEnd = $currentEnd->copy()->addDay()->toDateString();

        $pivots = ShiftWorker::withoutTrashed()
            ->with('shift.craft')
            ->where('employable_type', $worker->getMorphClass())
            ->where('employable_id', $worker->getKey())
            ->where('shift_id', '!=', $shift->id)
            ->whereHas('shift', function ($query) use ($scopeStart, $scopeEnd): void {
                $query->where('start_date', '<=', $scopeEnd)
                    ->where('end_date', '>=', $scopeStart);
            })
            ->get();

        $conflicts = [];
        foreach ($pivots as $pivot) {
            $otherShift = $pivot->shift;
            if (!$otherShift) {
                continue;
            }

            $interval = self::resolvePivotInterval($pivot, $otherShift);
            if ($interval === null) {
                continue;
            }
            [$otherStart, $otherEnd] = $interval;

            if (!self::intervalsOverlap($currentStart, $currentEnd, $otherStart, $otherEnd)) {
                continue;
            }

            $craft = $otherShift->craft?->abbreviation ?: ($otherShift->craft?->name ?? '');
            $conflicts[] = [
                'type' => self::TYPE_OVERLAP,
                'label' => __('Overlaps with another shift'),
                'detail' => trim(sprintf(
                    '%s %s %s–%s',
                    $craft,
                    $otherStart->format('d.m.Y'),
                    $otherStart->format('H:i'),
                    $otherEnd->format('H:i'),
                )),
            ];
        }

        return $conflicts;
    }

    /**
     * Urlaub/Freier Tag (ganztägig gewertet) und Nichtverfügbarkeit (ganztags oder
     * Zeitfenster) für alle Kalendertage, die die effektive Schichtzeit berührt.
     *
     * @return list<array{type: string, label: string, detail: string}>
     */
    private function absenceConflicts(Model $worker, Carbon $currentStart, Carbon $currentEnd): array
    {
        $vacations = Vacation::query()
            ->where('vacationer_type', $worker->getMorphClass())
            ->where('vacationer_id', $worker->getKey())
            ->whereDate('date', '>=', $currentStart->toDateString())
            ->whereDate('date', '<=', $currentEnd->toDateString())
            ->whereIn('type', [
                VacationType::OFF_WORK->value,
                VacationType::FREE_WORK->value,
                VacationType::NOT_AVAILABLE->value,
            ])
            ->orderBy('date')
            ->get();

        $conflicts = [];
        foreach ($vacations as $vacation) {
            $date = Carbon::parse($vacation->date);
            $type = $vacation->type instanceof VacationType ? $vacation->type->value : (string) $vacation->type;

            if ($type === VacationType::NOT_AVAILABLE->value) {
                if (!$vacation->full_day && $vacation->start_time && $vacation->end_time) {
                    $window = self::resolveInterval(
                        $date,
                        $vacation->start_time,
                        $date,
                        $vacation->end_time,
                    );
                    if ($window === null || !self::intervalsOverlap($currentStart, $currentEnd, $window[0], $window[1])) {
                        continue;
                    }
                    $conflicts[] = [
                        'type' => self::TYPE_UNAVAILABLE,
                        'label' => __('Not available'),
                        'detail' => sprintf(
                            '%s %s–%s',
                            $date->format('d.m.Y'),
                            $window[0]->format('H:i'),
                            $window[1]->format('H:i'),
                        ),
                    ];
                    continue;
                }

                $conflicts[] = [
                    'type' => self::TYPE_UNAVAILABLE,
                    'label' => __('Not available'),
                    'detail' => $date->format('d.m.Y') . ' (' . __('all day') . ')',
                ];
                continue;
            }

            $conflicts[] = [
                'type' => self::TYPE_VACATION,
                'label' => $type === VacationType::FREE_WORK->value ? __('Free day') : __('Vacation'),
                'detail' => $date->format('d.m.Y'),
            ];
        }

        return $conflicts;
    }

    private function displayName(Model $worker): string
    {
        if ($worker instanceof User) {
            return $worker->full_name ?? trim(($worker->first_name ?? '') . ' ' . ($worker->last_name ?? ''));
        }

        return (string) ($worker->name ?? '');
    }
}
