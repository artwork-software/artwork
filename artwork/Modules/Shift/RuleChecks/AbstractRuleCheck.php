<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Contracts\ShiftRuleCheckInterface;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Basis aller Regelprüfungen.
 *
 * Personenzeiten: Überall gelten die EFFEKTIVEN Zeiten der Person — die Pivot-Zeiten aus
 * shift_workers (start_date/end_date/start_time/end_time), falls gesetzt, sonst die Schichtzeiten;
 * individuelle Zeiten zählen zusätzlich. Gemeinsame Quelle ist getWorkIntervals() ("effektive
 * Arbeitsintervalle der Person"), aus der Tagesbeginn, Tagesende, Tagesstunden und Ruhezeiten
 * abgeleitet werden.
 *
 * Datenkontext: Setzt der ShiftRuleService einen ShiftRuleCheckContext, arbeiten alle Helfer auf den
 * dort einmal geladenen Daten. Ohne Kontext (Unit-Tests, Einzelaufrufe) fragen sie direkt ab.
 */
abstract class AbstractRuleCheck implements ShiftRuleCheckInterface
{
    protected ?ShiftRuleCheckContext $context = null;

    private ?SpecialDayService $specialDayServiceInstance = null;

    public function setContext(?ShiftRuleCheckContext $context): void
    {
        $this->context = $context;
        // Sondertag-Cache nicht über Läufe hinweg halten (Feiertage können sich ändern).
        $this->specialDayServiceInstance = null;
    }

    public function getContext(): ?ShiftRuleCheckContext
    {
        return $this->context;
    }

    /**
     * Geplante Arbeitsstunden einer Person an einem Tag = Schichten (mit personenindividuellen
     * Pivot-Zeiten, Pause einmal am ersten Schichttag) PLUS individuelle Zeiten (netto, Pause am
     * ersten Tag). 5 h Schicht + 6 h individuelle Zeit reißen damit ein Tagesmaximum von 10 h.
     */
    protected function getPlannedWorkingHoursForDay(User $user, Carbon $date): float
    {
        $dayKey = $date->toDateString();

        if ($this->context !== null && $this->context->covers($date, $date)) {
            $shiftMinutes = (int) ($this->context->shiftMinutesPerDay()[$dayKey] ?? 0);
        } else {
            $shiftMinutes = (int) (app(WorkTimeCalculationService::class)
                ->shiftMinutesPerDay($user, $date->copy(), $date->copy())[$dayKey] ?? 0);
        }

        return ($shiftMinutes + $this->getIndividualTimeMinutesForDay($user, $date)) / 60.0;
    }

    /**
     * Minuten aus individuellen Zeiten, die den Tag berühren (auf den Tag zugeschnitten);
     * die Pause wird nur am ersten Tag des Eintrags abgezogen.
     */
    protected function getIndividualTimeMinutesForDay(User $user, Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->startOfDay()->addDay();

        $totalMinutes = 0;
        foreach ($this->getIndividualTimesForRange($user, $date, $date) as $it) {
            $itStart = Carbon::parse($it->start_date)->startOfDay();
            $itEnd = Carbon::parse($it->end_date)->startOfDay();

            if (!empty($it->start_time)) {
                $itStart->setTimeFromTimeString((string) $it->start_time);
            }
            if (!empty($it->end_time)) {
                $itEnd->setTimeFromTimeString((string) $it->end_time);
            } else {
                $itEnd->addDay();
            }

            $segStart = $itStart->greaterThan($dayStart) ? $itStart : $dayStart;
            $segEnd = $itEnd->lessThan($dayEnd) ? $itEnd : $dayEnd;
            if ($segStart->greaterThanOrEqualTo($segEnd)) {
                continue;
            }

            $minutes = $segStart->diffInMinutes($segEnd);
            if ($date->isSameDay(Carbon::parse($it->start_date))) {
                $minutes -= (int) ($it->break_minutes ?? 0);
            }
            $totalMinutes += max(0, $minutes);
        }

        return $totalMinutes;
    }

    /**
     * Zeitraum, für den dieser Check im Lauf [$startDate, $endDate] verbindlich Verstöße erzeugt bzw.
     * bestätigt hat. ShiftRuleService löscht nach dem Lauf nur innerhalb dieses Fensters nicht mehr
     * bestätigte automatische Verstöße. Standard: der übergebene Zeitraum. Checks, die einen
     * abweichenden Zeitraum abdecken (z. B. MinDaysBeforeCommit: heute bis heute+n), überschreiben.
     *
     * @return array{0: Carbon, 1: Carbon}|null null = dieser Lauf hat nichts verbindlich abgedeckt
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return [$startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()];
    }

    // ------------------------------------------------------------------
    // Datenzugriff: Kontext, sonst Direktabfrage
    // ------------------------------------------------------------------

    /**
     * Schichten der Person, die den Zeitraum berühren (Schicht- ODER Pivot-Zeitraum), mit Pivot.
     *
     * @return Collection<int, Shift>
     */
    protected function getShiftsForRange(User $user, Carbon $from, Carbon $to): Collection
    {
        $fromKey = $from->toDateString();
        $toKey = $to->toDateString();

        if ($this->context !== null && $this->context->covers($from, $to)) {
            return $this->context->shifts()->filter(function (Shift $shift) use ($fromKey, $toKey): bool {
                [$startKey, $endKey] = $this->effectiveShiftDateKeys($shift);
                return $startKey <= $toKey && $endKey >= $fromKey;
            })->values();
        }

        return $user->shifts()
            ->with('shiftGroup')
            ->where(function ($query) use ($fromKey, $toKey): void {
                $query->where(function ($sub) use ($fromKey, $toKey): void {
                    $sub->whereDate('shifts.start_date', '<=', $toKey)
                        ->whereDate('shifts.end_date', '>=', $fromKey);
                })->orWhere(function ($sub) use ($fromKey, $toKey): void {
                    $sub->whereDate('shift_workers.start_date', '<=', $toKey)
                        ->whereDate('shift_workers.end_date', '>=', $fromKey);
                });
            })
            ->orderBy('shifts.start_date')
            ->orderBy('shifts.start')
            ->get()
            ->filter(function (Shift $shift) use ($fromKey, $toKey): bool {
                // Effektiver (Pivot-)Zeitraum muss den Bereich wirklich berühren.
                [$startKey, $endKey] = $this->effectiveShiftDateKeys($shift);
                return $startKey <= $toKey && $endKey >= $fromKey;
            })
            ->values();
    }

    /**
     * @return Collection<int, IndividualTime>
     */
    protected function getIndividualTimesForRange(User $user, Carbon $from, Carbon $to): Collection
    {
        $fromKey = $from->toDateString();
        $toKey = $to->toDateString();

        if ($this->context !== null && $this->context->covers($from, $to)) {
            return $this->context->individualTimes()->filter(
                fn (IndividualTime $it): bool => (string) $it->start_date <= $toKey && (string) $it->end_date >= $fromKey
            )->values();
        }

        return $user->individualTimes()
            ->whereDate('start_date', '<=', $toKey)
            ->whereDate('end_date', '>=', $fromKey)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Gewährte halbe Ersatzfreitage der Person im Zeitraum, gruppiert nach granted_date.
     *
     * @return Collection<string, Collection>
     */
    protected function getGrantedHalvesByDate(User $user, Carbon $from, Carbon $to): Collection
    {
        $fromKey = $from->toDateString();
        $toKey = $to->toDateString();

        if ($this->context !== null && $this->context->covers($from, $to)) {
            return $this->context->grantedHalvesByDate()
                ->filter(fn ($halves, string $dateKey): bool => $dateKey >= $fromKey && $dateKey <= $toKey);
        }

        return app(CompensationDayOffRepository::class)
            ->getGrantedHalvesForUserInRange($user->id, $fromKey, $toKey)
            ->groupBy(fn ($half): string => Carbon::parse($half->granted_date)->format('Y-m-d'));
    }

    /**
     * Sondertage im Zeitraum ('Y-m-d' => Name).
     *
     * @return array<string, string>
     */
    protected function getSpecialDaysBetween(Carbon $from, Carbon $to): array
    {
        if ($this->context !== null && $this->context->covers($from, $to)) {
            $fromKey = $from->toDateString();
            $toKey = $to->toDateString();

            return array_filter(
                $this->context->specialDays(),
                fn (string $dateKey): bool => $dateKey >= $fromKey && $dateKey <= $toKey,
                ARRAY_FILTER_USE_KEY
            );
        }

        return $this->specialDayService()->specialDaysBetween($from, $to);
    }

    protected function specialDayService(): SpecialDayService
    {
        if ($this->context !== null) {
            return $this->context->specialDayService();
        }

        return $this->specialDayServiceInstance ??= app(SpecialDayService::class);
    }

    // ------------------------------------------------------------------
    // Effektive Arbeitsintervalle der Person
    // ------------------------------------------------------------------

    /**
     * Effektive Datumsgrenzen einer Schicht für die Person: Pivot-Datum vor Schichtdatum.
     *
     * @return array{0: string, 1: string} ['Y-m-d' Start, 'Y-m-d' Ende]
     */
    protected function effectiveShiftDateKeys(Shift $shift): array
    {
        $pivot = $shift->pivot ?? null;
        $start = $pivot?->start_date ?: $shift->start_date;
        $end = $pivot?->end_date ?: $shift->end_date;

        return [
            Carbon::parse((string) $start)->toDateString(),
            Carbon::parse((string) ($end ?: $start))->toDateString(),
        ];
    }

    /**
     * Effektives Arbeitsintervall der Person für eine Schicht (Pivot-Zeit vor Schichtzeit).
     *
     * @return array{start: Carbon, end: Carbon, start_key: string, end_key: string, source: string,
     *               shift: Shift, individual_time: null, break_minutes: int, group_id: int|null}|null
     */
    protected function shiftInterval(Shift $shift): ?array
    {
        $pivot = $shift->pivot ?? null;
        [$startKey, $endKey] = $this->effectiveShiftDateKeys($shift);
        $startTime = $pivot?->start_time ?: $shift->start;
        $endTime = $pivot?->end_time ?: $shift->end;

        if (!$startTime || !$endTime) {
            return null;
        }

        $start = Carbon::parse($startKey)->setTimeFromTimeString((string) $startTime);
        $end = Carbon::parse($endKey)->setTimeFromTimeString((string) $endTime);
        if ($end->lessThanOrEqualTo($start)) {
            // Zeit-only-Angabe über Mitternacht ohne Folgedatum
            $end->addDay();
            $endKey = $end->toDateString();
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_key' => $startKey,
            'end_key' => $endKey,
            'source' => 'shift',
            'shift' => $shift,
            'individual_time' => null,
            'break_minutes' => (int) ($shift->break_minutes ?? 0),
            'group_id' => $shift->shift_group_id,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, start_key: string, end_key: string, source: string,
     *               shift: null, individual_time: IndividualTime, break_minutes: int, group_id: null}
     */
    protected function individualTimeInterval(IndividualTime $it): array
    {
        $startKey = Carbon::parse((string) $it->start_date)->toDateString();
        $endKey = Carbon::parse((string) ($it->end_date ?: $it->start_date))->toDateString();

        $start = Carbon::parse($startKey);
        $end = Carbon::parse($endKey);
        $start->setTimeFromTimeString($it->start_time ? (string) $it->start_time : '00:00:00');
        if ($it->end_time) {
            $end->setTimeFromTimeString((string) $it->end_time);
        } else {
            $end->setTime(23, 59, 59);
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_key' => $startKey,
            'end_key' => $endKey,
            'source' => 'individual',
            'shift' => null,
            'individual_time' => $it,
            'break_minutes' => (int) ($it->break_minutes ?? 0),
            'group_id' => null,
        ];
    }

    /**
     * Effektive Arbeitsintervalle der Person, die den Zeitraum berühren — Schichten (Pivot-Zeit,
     * sonst Schichtzeit) plus individuelle Zeiten, sortiert nach Beginn.
     *
     * @return list<array{start: Carbon, end: Carbon, start_key: string, end_key: string, source: string,
     *               shift: Shift|null, individual_time: IndividualTime|null, break_minutes: int, group_id: int|null}>
     */
    protected function getWorkIntervals(User $user, Carbon $from, Carbon $to, bool $includeIndividualTimes = true): array
    {
        $intervals = [];
        foreach ($this->getShiftsForRange($user, $from, $to) as $shift) {
            $interval = $this->shiftInterval($shift);
            if ($interval !== null) {
                $intervals[] = $interval;
            }
        }
        if ($includeIndividualTimes) {
            foreach ($this->getIndividualTimesForRange($user, $from, $to) as $it) {
                $intervals[] = $this->individualTimeInterval($it);
            }
        }

        usort($intervals, static function (array $a, array $b): int {
            return $a['start']->getTimestamp() <=> $b['start']->getTimestamp();
        });

        return $intervals;
    }

    /**
     * Intervalle, die an diesem Tag BEGINNEN (Schichttag = effektiver Starttag).
     *
     * @return list<array<string, mixed>>
     */
    protected function getWorkIntervalsStartingOn(User $user, Carbon $date, bool $includeIndividualTimes = true): array
    {
        $dayKey = $date->toDateString();

        return array_values(array_filter(
            $this->getWorkIntervals($user, $date, $date, $includeIndividualTimes),
            static fn (array $interval): bool => $interval['start_key'] === $dayKey
        ));
    }

    /**
     * Schicht der Person, die genau an diesem Tag beginnt (effektiver Starttag der Person; Über-
     * Mitternacht-Schichten zählen nur am Starttag). Im Gegensatz zu getShiftForUserOnDate() ohne Folgetage.
     */
    protected function getShiftStartingOnDate(User $user, Carbon $date): ?Shift
    {
        $intervals = $this->getWorkIntervalsStartingOn($user, $date, false);

        return $intervals[0]['shift'] ?? null;
    }

    /**
     * Erste Schicht der Person, deren effektiver Zeitraum den Tag berührt (inkl. Folgetage).
     */
    protected function getShiftForUserOnDate(User $user, Carbon $date): ?Shift
    {
        $intervals = $this->getWorkIntervals($user, $date, $date, false);

        return $intervals[0]['shift'] ?? null;
    }

    /**
     * Frühester Arbeitsbeginn der Person an diesem Tag (Schicht mit Pivot-Zeit oder individuelle Zeit,
     * die an diesem Tag beginnt).
     */
    protected function getEarliestShiftStartOfDay(User $user, Carbon $date): ?Carbon
    {
        $earliest = null;
        foreach ($this->getWorkIntervalsStartingOn($user, $date) as $interval) {
            if ($earliest === null || $interval['start']->lt($earliest)) {
                $earliest = $interval['start']->copy();
            }
        }

        return $earliest;
    }

    /**
     * Spätestes Arbeitsende der Person, das diesem Tag zuzurechnen ist: Intervalle, die an diesem Tag
     * enden, oder an diesem Tag beginnen und über Mitternacht gehen.
     */
    protected function getLatestShiftEndOfDay(User $user, Carbon $date): ?Carbon
    {
        $dayKey = $date->toDateString();
        $latest = null;

        foreach ($this->getWorkIntervals($user, $date, $date) as $interval) {
            $endsToday = $interval['end_key'] === $dayKey;
            $startsTodayPastMidnight = $interval['start_key'] === $dayKey && $interval['end_key'] > $dayKey;
            if (!$endsToday && !$startsTodayPastMidnight) {
                continue;
            }
            if ($latest === null || $interval['end']->gt($latest)) {
                $latest = $interval['end']->copy();
            }
        }

        return $latest;
    }

    // ------------------------------------------------------------------
    // Verstöße anlegen
    // ------------------------------------------------------------------

    protected function createViolation(
        ShiftRule $rule,
        Shift $shift,
        User $user,
        Carbon $date,
        array $violationData,
        string $severity = 'warning'
    ): ShiftRuleViolation {
        // Check if violation already exists for this combination
        $existingViolation = ShiftRuleViolation::where([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d')
        ])->first();

        if ($existingViolation) {
            // Only update violation data if still active — preserve resolved/ignored status
            if ($existingViolation->status === 'active') {
                $existingViolation->update([
                    'violation_data' => $violationData,
                    'severity' => $severity,
                ]);
            }
            return $existingViolation;
        }

        // Create new violation - this will trigger the workflow automatically
        return ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d'),
            'violation_data' => $violationData,
            'severity' => $severity,
            'status' => 'active'
        ]);
    }

    protected function isSpecialDay(Carbon $date): bool
    {
        return $this->specialDayService()->isSpecialDay($date);
    }

    protected function createViolationWithoutShift(
        ShiftRule $rule,
        User $user,
        Carbon $date,
        array $violationData,
        string $severity = 'warning'
    ): ShiftRuleViolation {
        // Dedupe on (rule, user, date) for shift-less violations.
        $existingViolation = ShiftRuleViolation::where([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d'),
        ])->whereNull('shift_id')->first();

        if ($existingViolation) {
            if ($existingViolation->status === 'active') {
                $existingViolation->update([
                    'violation_data' => $violationData,
                    'severity' => $severity,
                ]);
            }
            return $existingViolation;
        }

        return ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => null,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d'),
            'violation_data' => $violationData,
            'severity' => $severity,
            'status' => 'active'
        ]);
    }

    /**
     * Ruhezeit zwischen mehreren Arbeitsintervallen der Person am selben Tag (effektive Zeiten,
     * auf den Tag zugeschnitten). Verstoß, wenn das FOLGENDE Intervall eine Schicht ist.
     */
    protected function checkRestTimeBetweenShiftsOnSameDay(
        ShiftRule $rule,
        User $user,
        Carbon $date
    ): Collection {
        $violations = collect();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $segments = [];
        foreach ($this->getWorkIntervalsStartingOn($user, $date) as $interval) {
            $segStart = $interval['start']->greaterThan($dayStart) ? $interval['start']->copy() : $dayStart->copy();
            $segEnd = $interval['end']->lessThan($dayEnd) ? $interval['end']->copy() : $dayEnd->copy();
            if ($segStart < $segEnd) {
                $segments[] = [
                    'start' => $segStart,
                    'end' => $segEnd,
                    'type' => $interval['source'] === 'shift' ? 'shift' : 'it',
                    'shift' => $interval['shift'],
                ];
            }
        }

        if (count($segments) < 2) {
            return $violations;
        }

        usort($segments, static function (array $a, array $b): int {
            return $a['start']->getTimestamp() <=> $b['start']->getTimestamp();
        });

        // Check rest time between consecutive segments; create violation when the NEXT segment is a shift
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];

            // Skip overlapping or concurrent segments — no meaningful rest time to check
            if ($next['start']->lessThanOrEqualTo($current['end'])) {
                continue;
            }

            $restHours = $this->calculateRestHours($current['end'], $next['start']);
            if ($restHours < $rule->individual_number_value && $next['type'] === 'shift' && $next['shift']) {
                $violations->push($this->createViolation($rule, $next['shift'], $user, $date, [
                    'rest_hours' => $restHours,
                    'min_required' => $rule->individual_number_value,
                    'previous_segment_end' => $current['end']->format('Y-m-d H:i:s'),
                    'current_segment_start' => $next['start']->format('Y-m-d H:i:s'),
                    'next_segment_type' => 'shift',
                ]));
            }
        }

        return $violations;
    }

    protected function calculateRestHours(Carbon $endTime, Carbon $startTime): float
    {
        // Calculate the difference in hours - endTime is when last shift ended, startTime is when next shift starts
        // If start time is before or equal to end time, it means there's no gap (or overlap)
        if ($startTime <= $endTime) {
            return 0.0; // No rest time if shifts overlap or touch
        }

        // Always return a positive fractional hour difference for precise comparisons
        return $endTime->diffInMinutes($startTime) / 60.0;
    }

    protected function isWorkday(Carbon $date): bool
    {
        return !$date->isSunday() && !$this->isHoliday($date);
    }

    /**
     * Sonntag oder Sondertag. Sondertag = Feiertag mit gesetztem Flag "als Sondertag behandeln"
     * (zentrale Definition im SpecialDayService); Schulferien ohne Flag zählen nicht.
     */
    protected function isHoliday(Carbon $date): bool
    {
        if ($this->context !== null && $this->context->covers($date, $date)) {
            return $date->isSunday() || isset($this->context->specialDays()[$date->toDateString()]);
        }

        return $this->specialDayService()->isSundayOrSpecialDay($date);
    }
}
