<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Geteilter Dienst: Mindestabstand zwischen zwei Arbeitsabschnitten derselben Person am selben Tag
 * (NV Bühne § 65 Abs. 2: vier Stunden), unabhängig von Schichtgruppen.
 *
 * $rule->individual_number_value = Mindestabstand in Stunden. Betrachtet werden die Einsätze, die an
 * dem Tag beginnen (effektive Zeiten der Person, Schichten und individuelle Zeiten). Liegt zwischen
 * zwei aufeinanderfolgenden Einsätzen eine Lücke, die kürzer ist als der Mindestabstand, entsteht ein
 * Verstoß. Direkt aneinander anschließende oder sich überschneidende Einsätze sind KEIN geteilter
 * Dienst und bleiben unberührt.
 *
 * Zusatzparameter (ShiftRuleConfig): excluded_shift_group_ids. Gehört einer der beiden Einsätze zu
 * einer dieser Schichtgruppen (z. B. Endprobe), wird die Lücke nicht geprüft.
 *
 * Verhältnis zu restTimeBeforeWorkday / restTimeBeforeHoliday: Diese prüfen ohne skip_same_day
 * ebenfalls Lücken am selben Tag, aber gegen ihren eigenen (Nachtruhe-)Wert. Wer beide Regeln
 * zuweist, setzt dort skip_same_day, sonst meldet die Nachtruhe-Regel jeden geteilten Dienst.
 */
class RestTimeBetweenShiftsOnDayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        $minHours = (float) $rule->individual_number_value;
        $excludedGroupIds = (array) ShiftRuleConfig::get($rule, 'excluded_shift_group_ids');

        $intervalsByDate = [];
        foreach ($this->getWorkIntervals($user, $startDate, $endDate) as $interval) {
            $intervalsByDate[$interval['start_key']][] = $interval;
        }

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $intervals = $intervalsByDate[$date->toDateString()] ?? [];
            if (count($intervals) < 2) {
                continue;
            }

            // Bisher spätestes Ende und der Einsatz, der es gesetzt hat (Überschneidungen überbrücken)
            $previous = $intervals[0];
            for ($i = 1; $i < count($intervals); $i++) {
                $next = $intervals[$i];

                if ($next['start']->lessThanOrEqualTo($previous['end'])) {
                    if ($next['end']->greaterThan($previous['end'])) {
                        $previous = $next;
                    }
                    continue;
                }

                $restHours = $this->calculateRestHours($previous['end'], $next['start']);
                $excluded = $this->inGroups($previous, $excludedGroupIds) || $this->inGroups($next, $excludedGroupIds);

                if (!$excluded && $restHours < $minHours) {
                    $data = [
                        'type' => 'rest_time_between_shifts_on_day',
                        'rest_hours' => round($restHours, 2),
                        'min_required' => $minHours,
                        'previous_segment_end' => $previous['end']->format('Y-m-d H:i:s'),
                        'current_segment_start' => $next['start']->format('Y-m-d H:i:s'),
                    ];
                    $shift = $next['shift'] ?? $previous['shift'];
                    $violations->push(
                        $shift !== null
                            ? $this->createViolation($rule, $shift, $user, $date, $data)
                            : $this->createViolationWithoutShift($rule, $user, $date, $data)
                    );
                }

                $previous = $next;
            }
        }

        return $violations;
    }

    /**
     * @param array<string, mixed> $interval
     * @param array<int, int> $groupIds
     */
    private function inGroups(array $interval, array $groupIds): bool
    {
        return $interval['group_id'] !== null && in_array((int) $interval['group_id'], $groupIds, true);
    }

    public function getTriggerType(): string
    {
        return 'restTimeBetweenShiftsOnDay';
    }
}
