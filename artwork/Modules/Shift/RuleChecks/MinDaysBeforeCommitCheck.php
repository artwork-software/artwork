<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MinDaysBeforeCommitCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        // startOfDay: start_date ist eine DATE-Spalte (Vergleichswert 00:00) — mit
        // Zeitanteil fiel eine HEUTE beginnende Schicht aus dem Fenster, ausgerechnet
        // der dringendste Fall.
        $today = now()->startOfDay();
        $futureDate = $today->copy()->addDays($rule->individual_number_value);

        // Get non-committed shifts assigned to this user within the rule's time frame
        $shifts = Shift::where('is_committed', false)
            ->whereBetween('start_date', [$today, $futureDate])
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->get();

        foreach ($shifts as $shift) {
            $violations->push($this->createViolation($rule, $shift, $user, Carbon::parse($shift->start_date), [
                // Carbon 3 liefert Float-Tage — ganzzahlig ausgeben
                'days_until_shift' => (int) $today->diffInDays(Carbon::parse($shift->start_date)->startOfDay()),
                'min_required' => $rule->individual_number_value
            ]));
        }

        return $violations;
    }

    /**
     * Dieser Check prüft unabhängig vom übergebenen Zeitraum immer heute bis heute+n Tage. Nur dort
     * darf der Service nicht bestätigte Verstöße löschen — ältere Verstöße (Schichttag vorbei) und
     * spätere (außerhalb der Frist) hat der Lauf nicht beurteilt.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        $today = now()->startOfDay();
        $futureDate = $today->copy()->addDays((int) $rule->individual_number_value);

        $from = $startDate->copy()->startOfDay()->max($today);
        $to = $endDate->copy()->startOfDay()->min($futureDate);

        return $from->gt($to) ? null : [$from, $to];
    }

    public function getTriggerType(): string
    {
        return 'minDaysBeforeCommit';
    }

}