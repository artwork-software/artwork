<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Überstunden-Abbaufrist: $rule->individual_number_value = Tage vor Fristablauf, ab denen gewarnt wird.
 *
 * Datenquelle sind die Überstunden-Einträge (user_overtimes, gepflegt vom OvertimeService): je Person mit
 * aktiver Überstundenregel im Vertrag werden alle offenen Einträge (nicht abgebaut, nicht ausgezahlt —
 * Status open/payable mit Restminuten) betrachtet, deren Frist innerhalb der nächsten N Tage liegt
 * oder bereits überschritten ist. Jeder Eintrag erzeugt einen Verstoß OHNE Schicht am Fristdatum;
 * nach Fristablauf mit severity = error. Dedupe je (Regel, Person, Datum).
 *
 * Der Check ist unabhängig vom übergebenen Prüfzeitraum: er läuft auch für Personen ohne Schichten.
 */
class OvertimeDeadlineCheck extends AbstractRuleCheck
{
    /** Wie weit in die Vergangenheit offene (überfällige) Fristen verbindlich beurteilt werden. */
    private const LOOKBACK_YEARS = 2;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        if (!$this->overtimeRuleActiveFor($user)) {
            return $violations;
        }

        $today = now()->startOfDay();
        $warnDays = max(0, (int) $rule->individual_number_value);
        $warnUntil = $today->copy()->addDays($warnDays);

        $entries = UserOvertime::query()
            ->forUser($user->id)
            ->whereIn('status', [UserOvertime::STATUS_OPEN, UserOvertime::STATUS_PAYABLE])
            ->where('remaining_minutes', '>', 0)
            ->whereDate('deadline', '<=', $warnUntil->toDateString())
            ->whereDate('deadline', '>=', $today->copy()->subYears(self::LOOKBACK_YEARS)->toDateString())
            ->orderBy('deadline')
            ->get();

        foreach ($entries as $entry) {
            $deadline = Carbon::parse($entry->deadline)->startOfDay();
            // Carbon 3 liefert Float-Tage — ganzzahlig, negativ nach Fristablauf
            $daysLeft = (int) $today->diffInDays($deadline, false);
            $expired = $deadline->lt($today);

            $violations->push($this->createViolationWithoutShift(
                $rule,
                $user,
                $deadline,
                [
                    'type' => 'overtime_deadline',
                    'remaining_minutes' => (int) $entry->remaining_minutes,
                    'deadline' => $deadline->toDateString(),
                    'days_left' => $daysLeft,
                    'booking_day' => Carbon::parse($entry->date)->toDateString(),
                    'warn_days' => $warnDays,
                    'expired' => $expired,
                ],
                $expired ? 'error' : 'warning'
            ));
        }

        return $violations;
    }

    /**
     * Der Check beurteilt alle Fristen von (heute − Rückblick) bis (heute + N Tage) — unabhängig vom
     * Prüfzeitraum. Nur dort dürfen nicht bestätigte Verstöße gelöscht werden (z. B. nach Abbau/Auszahlung).
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        $today = now()->startOfDay();

        return [
            $startDate->copy()->startOfDay()->min($today->copy()->subYears(self::LOOKBACK_YEARS)),
            $endDate->copy()->startOfDay()->max($today->copy()->addDays(max(0, (int) $rule->individual_number_value))),
        ];
    }

    private function overtimeRuleActiveFor(User $user): bool
    {
        $resolver = app(ContractSettingsResolver::class);
        if ($resolver->assignFor($user) === null) {
            return false;
        }

        return $resolver->bool($user, 'overtime_rule_active');
    }

    public function getTriggerType(): string
    {
        return 'overtimeDeadline';
    }
}
