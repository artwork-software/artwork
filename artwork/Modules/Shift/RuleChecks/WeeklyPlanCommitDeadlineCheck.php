<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Wochenplan-Frist: Die Dienste einer Kalenderwoche (Mo–So) müssen bis zu einem festen Wochentag und
 * einer Uhrzeit der Vorwoche verbindlich sein (NV Bühne § 6 Abs. 1: Donnerstag 14 Uhr).
 *
 * Zusatzparameter (ShiftRuleConfig): weekday (ISO, 1 = Montag … 7 = Sonntag), time ("HH:MM"),
 * weeks_ahead (wie viele Wochen vor der Zielwoche die Frist liegt, Standard 1). Fällt der Fristtag auf
 * einen Sonntag oder Sondertag, gilt der Werktag davor. $rule->individual_number_value wird nicht
 * verwendet.
 *
 * Der Check ist unabhängig vom übergebenen Prüfzeitraum und beurteilt immer den Stand von jetzt: Für
 * jede Zielwoche ab der laufenden, deren Frist abgelaufen ist, entsteht je Person EIN Verstoß ohne
 * Schicht auf den Montag der Zielwoche, solange die Person dort noch nicht verbindliche Schichten ab
 * heute hat. Werden sie verbindlich geschaltet, räumt der nächste Lauf den Verstoß auf.
 */
class WeeklyPlanCommitDeadlineCheck extends AbstractRuleCheck
{
    /** Obergrenze der betrachteten Zielwochen ab der laufenden Woche (weeks_ahead ist höchstens 4). */
    private const MAX_TARGET_WEEKS = 6;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        $now = now();
        $today = $now->copy()->startOfDay();

        foreach ($this->overdueTargetWeeks($rule, $now) as $week) {
            [$monday, $sunday, $deadline] = $week;

            $openShifts = Shift::query()
                ->where('is_committed', false)
                ->whereBetween('start_date', [$monday->copy()->max($today), $sunday])
                ->whereHas('users', fn ($query) => $query->where('users.id', $user->id))
                ->count();

            if ($openShifts === 0) {
                continue;
            }

            $violations->push($this->createViolationWithoutShift(
                $rule,
                $user,
                $monday,
                [
                    'type' => 'weekly_plan_commit_deadline',
                    'open_shifts' => $openShifts,
                    'deadline' => $deadline->format('Y-m-d H:i'),
                    'week' => $monday->isoWeek(),
                    'week_start' => $monday->toDateString(),
                    'week_end' => $sunday->toDateString(),
                ],
                'warning',
                $this->lastContractDayIn($user, $monday, $sunday) ?? $monday
            ));
        }

        return $violations;
    }

    /**
     * Zielwochen ab der laufenden Woche, deren Frist zum Zeitpunkt $now abgelaufen ist.
     *
     * @return list<array{0: Carbon, 1: Carbon, 2: Carbon}> [Montag, Sonntag, Frist]
     */
    private function overdueTargetWeeks(ShiftRule $rule, Carbon $now): array
    {
        $weeks = [];
        $monday = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        for ($i = 0; $i < self::MAX_TARGET_WEEKS; $i++) {
            $deadline = $this->deadlineFor($rule, $monday);
            if ($now->lt($deadline)) {
                // Die Fristen späterer Wochen liegen noch weiter in der Zukunft.
                break;
            }
            $weeks[] = [$monday->copy(), $monday->copy()->addDays(6), $deadline];
            $monday->addWeek();
        }

        return $weeks;
    }

    /**
     * Frist für die Zielwoche, die an $targetMonday beginnt.
     */
    public function deadlineFor(ShiftRule $rule, Carbon $targetMonday): Carbon
    {
        $weekday = (int) ShiftRuleConfig::get($rule, 'weekday');
        $weeksAhead = (int) ShiftRuleConfig::get($rule, 'weeks_ahead');

        $day = $targetMonday->copy()->startOfDay()->subWeeks($weeksAhead)->addDays($weekday - 1);
        // Fristtag ist Sonntag oder Sondertag: der Werktag davor (höchstens eine Woche zurück).
        for ($i = 0; $i < 7 && !$this->isWorkday($day); $i++) {
            $day->subDay();
        }

        return $day->setTimeFromTimeString((string) ShiftRuleConfig::get($rule, 'time'));
    }

    /**
     * Beurteilt werden die Zielwochen mit abgelaufener Frist, unabhängig vom Prüfzeitraum. Nur dort
     * darf der Service nicht mehr bestätigte Verstöße löschen.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        $weeks = $this->overdueTargetWeeks($rule, now());
        if ($weeks === []) {
            return null;
        }

        return [$weeks[0][0]->copy(), $weeks[count($weeks) - 1][1]->copy()];
    }

    /**
     * Liest Schichten direkt und braucht keinen Datenkontext.
     */
    public function getContextRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return null;
    }

    public function getTriggerType(): string
    {
        return 'weeklyPlanCommitDeadline';
    }
}
