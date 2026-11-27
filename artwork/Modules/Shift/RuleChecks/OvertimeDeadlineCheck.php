<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;

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
 *
 * Vertragshistorie: Je Eintrag entscheidet der Vertrag, aus dem die Überstunden stammen (gültig am Buchungstag
 * des Eintrags). Er hat die Frist bestimmt (OvertimeService), aus ihm kommen der Schalter overtime_rule_active
 * und die Zuordnung der Regel. Endet der Vertrag vor der Frist (befristeter Vertrag, Austritt) oder gilt danach
 * ein Vertrag ohne Überstundenregel, wird für die bereits entstandenen Überstunden trotzdem gewarnt.
 */
class OvertimeDeadlineCheck extends AbstractRuleCheck
{
    /** Wie weit in die Vergangenheit offene (überfällige) Fristen verbindlich beurteilt werden. */
    private const LOOKBACK_YEARS = 2;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Ohne Arbeitszeitberechnung werden keine Überstunden fortgeschrieben — die Regel hätte
        // nur veraltete Einträge als Grundlage
        if (!WorkTimeAccounting::isEnabled()) {
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

        // Mehrere Einträge können dieselbe Frist haben (Frist in Kalenderwochen: alle Buchungstage einer
        // Woche enden am selben Sonntag). Der Verstoß je Fristtag trägt dann die Summe der offenen Minuten.
        /** @var array<string, array{minutes: int, first_booking_day: string, entries: int}> $totals */
        $totals = [];

        foreach ($entries as $entry) {
            $bookingDay = Carbon::parse($entry->date)->startOfDay();
            if (!$this->overtimeRuleActiveOn($user, $bookingDay)) {
                continue;
            }
            $deadline = Carbon::parse($entry->deadline)->startOfDay();
            // Carbon 3 liefert Float-Tage — ganzzahlig, negativ nach Fristablauf
            $daysLeft = (int) $today->diffInDays($deadline, false);
            $expired = $deadline->lt($today);

            $key = $deadline->toDateString();
            $previous = $totals[$key] ?? null;
            $totals[$key] = [
                'minutes' => ($previous['minutes'] ?? 0) + (int) $entry->remaining_minutes,
                'first_booking_day' => min($previous['first_booking_day'] ?? $bookingDay->toDateString(), $bookingDay->toDateString()),
                'entries' => ($previous['entries'] ?? 0) + 1,
            ];

            $violation = $this->createViolationWithoutShift(
                $rule,
                $user,
                $deadline,
                [
                    'type' => 'overtime_deadline',
                    'remaining_minutes' => $totals[$key]['minutes'],
                    'deadline' => $key,
                    'days_left' => $daysLeft,
                    'booking_day' => $totals[$key]['first_booking_day'],
                    'entries' => $totals[$key]['entries'],
                    'warn_days' => $warnDays,
                    'expired' => $expired,
                ],
                $expired ? 'error' : 'warning',
                $bookingDay
            );

            if ($violation === null) {
                // Die Regel gilt für diesen Eintrag nicht (Vertrag am Buchungstag): nicht mitzählen.
                if ($previous === null) {
                    unset($totals[$key]);
                } else {
                    $totals[$key] = $previous;
                }
                continue;
            }
            $violations->put($key, $violation);
        }

        return $violations->values();
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

    /**
     * Liest nur user_overtimes, keine Schichten/Individualzeiten — der Datenkontext muss dafür nicht
     * um den Zwei-Jahres-Rückblick erweitert werden.
     */
    public function getContextRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return null;
    }

    /**
     * Überstundenregel im Vertrag, der am Buchungstag des Eintrags galt (ohne Vertrag: nein).
     */
    private function overtimeRuleActiveOn(User $user, Carbon $bookingDay): bool
    {
        $resolver = app(ContractSettingsResolver::class);
        if ($resolver->assignFor($user, $bookingDay) === null) {
            return false;
        }

        return $resolver->bool($user, 'overtime_rule_active', false, $bookingDay);
    }

    public function getTriggerType(): string
    {
        return 'overtimeDeadline';
    }
}
