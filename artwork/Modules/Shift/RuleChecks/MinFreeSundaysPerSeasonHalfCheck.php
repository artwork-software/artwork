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
 * X = $rule->individual_number_value; ist der Wert 0/leer, gilt der Zielwert aus dem Vertrag je Hälfte
 * (ShiftKpiTrackingService::extractTargets() → free_sundays_sat_mon_per_half, target_half1/target_half2:
 * aus den in der Hälfte gültigen Verträgen, bei einem Wechsel anteilig). Ist-Werte je Hälfte
 * liefert ShiftKpiTrackingService::computeForUser() (abgeschlossene Tage < heute). Dazu kommen die
 * "noch möglichen" Sonntage der laufenden Hälfte: Sonntage ab heute bis Hälftenende, an denen die
 * Person weder Schicht (effektive Zeiten) noch individuelle Zeit hat.
 *
 * Ob die Regel greift, entscheidet der Vertrag des letzten Tages der Hälfte, an dem ein Vertrag gilt (auch
 * wenn er vor dem letzten Sonntag endet). Das Vertragsziel ist wie im Info-Fenster anteilig über die Hälfte
 * gerechnet (Tage ohne Vertrag zählen 0), damit Regel und Kennzahl denselben Wert zeigen.
 *
 * Verstoß OHNE Schicht auf den letzten Sonntag der Hälfte, wenn ist + möglich < X (Ziel nicht mehr
 * erreichbar) oder wenn die Hälfte abgeschlossen ist und ist < X. Ohne Spielzeit-Einstellung
 * (Toolsettings) gilt das laufende Kalenderjahr als Spielzeit (SeasonWindowResolver).
 */
class MinFreeSundaysPerSeasonHalfCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        /** @var ShiftKpiTrackingService $kpiService */
        $kpiService = app(ShiftKpiTrackingService::class);
        // Immer gesetzt: konfigurierte Spielzeit oder Kalenderjahr-Fallback
        [$seasonStart, $seasonEnd] = $kpiService->getSeasonBounds();

        $ruleValue = (int) round((float) $rule->individual_number_value);
        $contractTargets = $ruleValue > 0 ? null : $kpiService->seasonTargets($user, $seasonStart, $seasonEnd);
        $targets = $this->targetsFor($ruleValue, $contractTargets);
        if (max($targets) <= 0) {
            return $violations;
        }

        // Zielwerte aus targetsFor() wiederverwenden statt sie in computeForUser() erneut zu rechnen
        $kpis = $kpiService->computeForUser($user, $seasonStart, $seasonEnd, $contractTargets);
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
            $target = $targets[$half];
            if ($target <= 0 || $window['end']->lt($window['start'])) {
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
            ], $completed ? 'error' : 'warning', $this->lastContractDayIn(
                $user,
                $window['start'],
                $window['end']
            ) ?? $lastSunday));
        }

        return $violations;
    }

    /**
     * Die Verstoßdaten (letzter Sonntag je Hälfte) liegen in der Spielzeit, nicht zwingend im
     * Prüfzeitraum — der Lauf beurteilt daher die gesamte Spielzeit verbindlich.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        [$seasonStart, $seasonEnd] = app(ShiftKpiTrackingService::class)->getSeasonBounds();

        return [
            $startDate->copy()->startOfDay()->min($seasonStart->copy()->startOfDay()),
            $endDate->copy()->startOfDay()->max($seasonEnd->copy()->startOfDay()),
        ];
    }

    /**
     * Kein Kontextfenster: Die Spielzeit (bis zu einem Jahr) würde den gemeinsamen Datenkontext für jeden
     * Lauf aufblähen. Die Ist-Werte rechnet ohnehin der ShiftKpiTrackingService; die noch möglichen
     * Sonntage (heute bis Hälftenende) liest possibleFreeSundays() bei fehlender Abdeckung direkt.
     */
    public function getContextRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return null;
    }

    /**
     * @param array<string, array<string, mixed>>|null $contractTargets seasonTargets(), null bei Regelwert
     * @return array{1: int, 2: int} Ziel je Hälfte
     */
    private function targetsFor(int $ruleValue, ?array $contractTargets): array
    {
        if ($ruleValue > 0) {
            return [1 => $ruleValue, 2 => $ruleValue];
        }

        $target = $contractTargets['free_sundays_sat_mon_per_half'] ?? [];

        return [
            1 => (int) ($target['target_half1'] ?? 0),
            2 => (int) ($target['target_half2'] ?? 0),
        ];
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
