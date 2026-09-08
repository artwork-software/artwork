<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Services\ShiftKpiTrackingService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mindestens X freie Sonntage mit freiem Samstag oder Montag je Spielzeithälfte.
 *
 * X = $rule->individual_number_value; ist der Wert 0/leer, gilt der Zielwert aus dem Vertrag
 * (ShiftKpiTrackingService::extractTargets() → free_sundays_sat_mon_per_half). Ist-Werte je Hälfte
 * liefert ShiftKpiTrackingService::computeForUser() (abgeschlossene Tage < heute). Dazu kommen die
 * "noch möglichen" Sonntage der laufenden Hälfte: Sonntage ab heute bis Hälftenende, an denen die
 * Person weder Schicht (effektive Zeiten) noch individuelle Zeit hat.
 *
 * Verstoß OHNE Schicht auf den letzten Sonntag der Hälfte, wenn ist + möglich < X (Ziel nicht mehr
 * erreichbar) oder wenn die Hälfte abgeschlossen ist und ist < X. Ohne Spielzeit-Einstellung
 * (Toolsettings) passiert nichts — kein Verstoß, keine Exception.
 */
class MinFreeSundaysPerSeasonHalfCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        /** @var ShiftKpiTrackingService $kpiService */
        $kpiService = app(ShiftKpiTrackingService::class);
        $bounds = $kpiService->getSeasonBounds();
        if ($bounds === null) {
            return $violations;
        }
        [$seasonStart, $seasonEnd] = $bounds;

        $target = $this->targetFor($rule, $user, $kpiService);
        if ($target <= 0) {
            return $violations;
        }

        $kpis = $kpiService->computeForUser($user, $seasonStart, $seasonEnd);
        $midpoint = $kpiService->getSeasonMidpoint($seasonStart, $seasonEnd);
        $today = now()->startOfDay();

        $halves = [
            1 => [
                'start' => $seasonStart->copy()->startOfDay(),
                'end' => $midpoint->copy()->subDay()->startOfDay(),
                'have' => (int) ($kpis['free_sundays_sat_mon_half1'] ?? 0),
            ],
            2 => [
                'start' => $midpoint->copy()->startOfDay(),
                'end' => $seasonEnd->copy()->startOfDay(),
                'have' => (int) ($kpis['free_sundays_sat_mon_half2'] ?? 0),
            ],
        ];

        foreach ($halves as $half => $window) {
            if ($window['end']->lt($window['start'])) {
                continue;
            }

            $lastSunday = $this->lastSundayIn($window['start'], $window['end']);
            if ($lastSunday === null) {
                continue;
            }

            $completed = $window['end']->lt($today);
            $notStarted = $window['start']->gt($today);
            if ($notStarted) {
                // Zukünftige Hälfte: noch nichts zu beurteilen.
                continue;
            }

            $possible = $completed ? 0 : $this->possibleFreeSundays($user, $today, $window['end']);
            $have = $window['have'];

            $isViolation = $completed ? $have < $target : ($have + $possible) < $target;
            if (!$isViolation) {
                continue;
            }

            $violations->push($this->createViolationWithoutShift($rule, $user, $lastSunday, [
                'type' => 'min_free_sundays_per_season_half',
                'half' => $half,
                'have' => $have,
                'target' => $target,
                'possible' => $possible,
                'half_start' => $window['start']->toDateString(),
                'half_end' => $window['end']->toDateString(),
                'completed' => $completed,
            ], $completed ? 'error' : 'warning'));
        }

        return $violations;
    }

    /**
     * Die Verstoßdaten (letzter Sonntag je Hälfte) liegen in der Spielzeit, nicht zwingend im
     * Prüfzeitraum — der Lauf beurteilt daher die gesamte Spielzeit verbindlich.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        $bounds = app(ShiftKpiTrackingService::class)->getSeasonBounds();
        if ($bounds === null) {
            return null;
        }
        [$seasonStart, $seasonEnd] = $bounds;

        return [
            $startDate->copy()->startOfDay()->min($seasonStart->copy()->startOfDay()),
            $endDate->copy()->startOfDay()->max($seasonEnd->copy()->startOfDay()),
        ];
    }

    private function targetFor(ShiftRule $rule, User $user, ShiftKpiTrackingService $kpiService): int
    {
        $ruleValue = (int) round((float) $rule->individual_number_value);
        if ($ruleValue > 0) {
            return $ruleValue;
        }

        $targets = $kpiService->extractTargets($user);

        return (int) ($targets['free_sundays_sat_mon_per_half']['value'] ?? 0);
    }

    /**
     * Sonntage ab $from (inkl.) bis $to (inkl.), an denen die Person weder Schicht noch Individualzeit hat.
     */
    private function possibleFreeSundays(User $user, Carbon $from, Carbon $to): int
    {
        if ($to->lt($from)) {
            return 0;
        }

        $occupied = [];
        foreach ($this->getWorkIntervals($user, $from, $to) as $interval) {
            $cursor = Carbon::parse($interval['start_key']);
            $last = Carbon::parse($interval['end_key']);
            while ($cursor->lte($last)) {
                $occupied[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        $count = 0;
        $cursor = $from->copy()->startOfDay();
        while ($cursor->dayOfWeek !== Carbon::SUNDAY && $cursor->lte($to)) {
            $cursor->addDay();
        }
        for ($sunday = $cursor; $sunday->lte($to); $sunday->addWeek()) {
            if (!isset($occupied[$sunday->toDateString()])) {
                $count++;
            }
        }

        return $count;
    }

    private function lastSundayIn(Carbon $from, Carbon $to): ?Carbon
    {
        $cursor = $to->copy()->startOfDay();
        while ($cursor->dayOfWeek !== Carbon::SUNDAY && $cursor->gte($from)) {
            $cursor->subDay();
        }

        return $cursor->gte($from) ? $cursor : null;
    }

    public function getTriggerType(): string
    {
        return 'minFreeSundaysPerSeasonHalf';
    }
}
