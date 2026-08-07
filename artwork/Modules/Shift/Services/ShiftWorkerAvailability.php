<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * Prüft, ob eine zugewiesene Person am Schichttag einen anderen
 * Verfügbarkeitsstatus als "Verfügbar" hat (z.B. nachträglich Krankheit).
 * Die Zuweisung bleibt dabei bestehen (Stundenabrechnung, insbesondere bei
 * festgeschriebenen Schichten) — der Konflikt wird nur visualisiert.
 */
class ShiftWorkerAvailability
{
    /**
     * Schwerste zuerst — bei mehreren konfliktierenden Einträgen am Tag gewinnt
     * "Nicht verfügbar" (z.B. krank) vor "Arbeitsfreier Tag" vor "Frei".
     */
    private const STATUS_SEVERITY = [
        VacationType::NOT_AVAILABLE->value,
        VacationType::OFF_WORK->value,
    ];

    public static function isWorkerUnavailable(Shift $shift, User|Freelancer|ServiceProvider $worker): bool
    {
        return self::getWorkerUnavailableStatus($shift, $worker) !== null;
    }

    /**
     * Verfügbarkeitsstatus (Vacation-Type), der mit dem Einsatzfenster der Person
     * kollidiert — null, wenn die Person verfügbar ist.
     */
    public static function getWorkerUnavailableStatus(
        Shift $shift,
        User|Freelancer|ServiceProvider $worker
    ): ?string {
        $shiftStartDate = $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null;

        if ($shiftStartDate === null) {
            return null;
        }

        $shiftEndDate = $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : $shiftStartDate;

        if (!$worker->relationLoaded('vacations')) {
            $worker->load([
                'vacations' => fn ($query) => $query
                    ->without(['series', 'conflicts'])
                    ->whereBetween('date', [$shiftStartDate, $shiftEndDate]),
            ]);
        }

        if ($worker->vacations->isEmpty()) {
            return null;
        }

        [$startDate, $endDate, $workStart, $workEnd] = self::resolveWorkWindow(
            $shift,
            $worker,
            $shiftStartDate,
            $shiftEndDate
        );

        $conflictingStatus = null;
        foreach ($worker->vacations as $vacation) {
            if (!self::vacationConflicts($vacation, $startDate, $endDate, $workStart, $workEnd)) {
                continue;
            }

            $type = $vacation->type instanceof VacationType ? $vacation->type->value : $vacation->type;
            if ($conflictingStatus === null || self::severity($type) < self::severity($conflictingStatus)) {
                $conflictingStatus = $type;
            }
        }

        return $conflictingStatus;
    }

    private static function severity(string $type): int
    {
        $index = array_search($type, self::STATUS_SEVERITY, true);

        return $index === false ? count(self::STATUS_SEVERITY) : $index;
    }

    /**
     * Einsatzfenster der Person: individuelle Pivot-Zeiten haben Vorrang vor den Schichtzeiten.
     *
     * @return array{0: string, 1: string, 2: Carbon, 3: Carbon}
     */
    private static function resolveWorkWindow(
        Shift $shift,
        User|Freelancer|ServiceProvider $worker,
        string $shiftStartDate,
        string $shiftEndDate
    ): array {
        $pivot = $worker->pivot;

        $startDate = $pivot?->start_date ? Carbon::parse($pivot->start_date)->toDateString() : $shiftStartDate;
        $endDate = $pivot?->end_date ? Carbon::parse($pivot->end_date)->toDateString() : $shiftEndDate;
        $startTime = self::normalizeTime($pivot?->start_time) ?? self::normalizeTime($shift->start) ?? '00:00';
        $endTime = self::normalizeTime($pivot?->end_time) ?? self::normalizeTime($shift->end) ?? '23:59';

        $workStart = Carbon::parse($startDate . ' ' . $startTime);
        $workEnd = Carbon::parse($endDate . ' ' . $endTime);
        if ($workEnd->lessThanOrEqualTo($workStart)) {
            $workEnd->addDay();
        }

        return [$startDate, $endDate, $workStart, $workEnd];
    }

    private static function vacationConflicts(
        Vacation $vacation,
        string $startDate,
        string $endDate,
        Carbon $workStart,
        Carbon $workEnd
    ): bool {
        $type = $vacation->type instanceof VacationType ? $vacation->type->value : $vacation->type;
        if ($type === VacationType::AVAILABLE->value) {
            return false;
        }

        $vacationDate = Carbon::parse($vacation->date)->toDateString();
        if ($vacationDate < $startDate || $vacationDate > $endDate) {
            return false;
        }

        if ($vacation->full_day || !$vacation->start_time || !$vacation->end_time) {
            return true;
        }

        $vacationStart = Carbon::parse($vacationDate . ' ' . self::normalizeTime($vacation->start_time));
        $vacationEnd = Carbon::parse($vacationDate . ' ' . self::normalizeTime($vacation->end_time));
        if ($vacationEnd->lessThanOrEqualTo($vacationStart)) {
            $vacationEnd->addDay();
        }

        return $vacationStart->lessThan($workEnd) && $vacationEnd->greaterThan($workStart);
    }

    private static function normalizeTime(mixed $time): ?string
    {
        if ($time instanceof DateTimeInterface) {
            return $time->format('H:i');
        }

        if (is_string($time) && $time !== '') {
            return substr($time, 0, 5);
        }

        return null;
    }
}
