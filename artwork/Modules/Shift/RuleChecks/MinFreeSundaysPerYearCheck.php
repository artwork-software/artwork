<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mindestens X freie Sonntage pro Kalenderjahr (ArbZG § 11 Abs. 1, Standard 15).
 *
 * X = $rule->individual_number_value; leer/0 = 15. Freier Sonntag = Sonntag ohne Schicht (effektive
 * Pivot-Zeiten, Über-Mitternacht-Schichten belegen beide Tage) und ohne individuelle Zeit.
 *
 * Je Kalenderjahr, das der Prüfzeitraum berührt, werden alle Sonntage des Jahres betrachtet: die freien
 * Sonntage vor heute ("have") plus die noch unbelegten Sonntage ab heute ("possible"). Ist die Summe
 * kleiner als X, ist das Ziel rechnerisch nicht mehr erreichbar (verbleibende freie Sonntage < noch
 * nötige) — Verstoß OHNE Schicht auf den letzten Sonntag des Jahres (Warnung; nach Jahresende Fehler).
 */
class MinFreeSundaysPerYearCheck extends AbstractRuleCheck
{
    public const DEFAULT_TARGET = 15;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $target = $this->targetFor($rule);
        $today = now()->startOfDay();

        for ($year = (int) $startDate->year; $year <= (int) $endDate->year; $year++) {
            $yearStart = Carbon::create($year, 1, 1)->startOfDay();
            $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
            $lastSunday = $this->lastSundayIn($yearStart, $yearEnd);
            if ($lastSunday === null) {
                continue;
            }

            $occupied = $this->getOccupiedDayKeys($user, $yearStart, $yearEnd);

            $have = 0;
            $possible = 0;
            $sunday = $yearStart->copy();
            while ($sunday->dayOfWeek !== Carbon::SUNDAY) {
                $sunday->addDay();
            }
            for (; $sunday->lte($yearEnd); $sunday->addWeek()) {
                if (isset($occupied[$sunday->toDateString()])) {
                    continue;
                }
                if ($sunday->lt($today)) {
                    $have++;
                } else {
                    $possible++;
                }
            }

            if ($have + $possible >= $target) {
                continue;
            }

            $completed = $yearEnd->lt($today);

            $violations->push($this->createViolationWithoutShift($rule, $user, $lastSunday, [
                'type' => 'min_free_sundays_per_year',
                'year' => $year,
                'have' => $have,
                'possible' => $possible,
                'target' => $target,
                'completed' => $completed,
            ], $completed ? 'error' : 'warning'));
        }

        return $violations;
    }

    /**
     * Der Verstoß liegt auf dem letzten Sonntag des Jahres — der Lauf beurteilt daher die berührten
     * Kalenderjahre vollständig.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return [
            $startDate->copy()->startOfYear()->startOfDay(),
            $endDate->copy()->endOfYear()->startOfDay(),
        ];
    }

    /**
     * Kein Kontextfenster: Das Jahresfenster würde den gemeinsamen Datenkontext für JEDEN Lauf (auch die
     * synchrone Neuprüfung nach Drag&Drop) auf ein volles Jahr aufblähen. Die Belegungstage des Jahres
     * holt getOccupiedDayKeys() stattdessen direkt (Fallback ohne Kontextabdeckung, zwei Abfragen je Jahr).
     */
    public function getContextRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return null;
    }

    private function targetFor(ShiftRule $rule): int
    {
        $ruleValue = (int) round((float) $rule->individual_number_value);

        return $ruleValue > 0 ? $ruleValue : self::DEFAULT_TARGET;
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
        return 'minFreeSundaysPerYear';
    }
}
