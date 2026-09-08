<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

/**
 * Dreimonatsdurchschnitt (Vertragsflag `use_three_month_average_for_target_reduction`):
 * Durchschnitt der Ist-Minuten DESSELBEN WOCHENTAGS über die drei vollständig abgeschlossenen
 * Kalendermonate vor dem Monat des Zieltags. Es zählen nur Tage mit tatsächlicher Arbeit;
 * Krank (NOT_AVAILABLE) und Urlaub (OFF_WORK) sind ausgeschlossen.
 */
class ThreeMonthAverageTargetService
{
    /**
     * null = keine gearbeiteten Tage dieses Wochentags im Referenzfenster. Der Fallback (das
     * Tagessoll des jeweils angefragten Wochentags) darf NICHT unter dem Zeitraum-Key
     * gecacht werden — er unterscheidet sich pro Aufruf.
     *
     * @var array<string, int|null>
     */
    private array $averageCache = [];

    public function __construct(private readonly ContractSettingsResolver $contractSettings)
    {
    }

    /**
     * Durchschnitt der Ist-Minuten des Wochentags von $targetDate im Referenzfenster;
     * ohne Daten für diesen Wochentag $fallbackMinutes (= Muster-Soll des Wochentags).
     */
    public function averageMinutesFor(User $user, Carbon $targetDate, int $fallbackMinutes): int
    {
        $weekday = $targetDate->dayOfWeek;
        $referenceEnd = $targetDate->copy()->subMonthNoOverflow()->endOfMonth();
        $referenceStart = $referenceEnd->copy()->subMonthsNoOverflow(2)->startOfMonth();
        $cacheKey = implode(':', [
            $user->id,
            $referenceStart->toDateString(),
            $referenceEnd->toDateString(),
            $weekday,
        ]);

        if (array_key_exists($cacheKey, $this->averageCache)) {
            return $this->averageCache[$cacheKey] ?? max(0, $fallbackMinutes);
        }

        $excludedDates = $user->vacations()
            ->whereBetween('date', [$referenceStart->toDateString(), $referenceEnd->toDateString()])
            ->where(function ($query): void {
                $query->whereIn('type', ['NOT_AVAILABLE', 'OFF_WORK'])
                    ->orWhere('comment', 'OFF_WORK');
            })
            ->pluck('date')
            ->map(static fn ($date): string => Carbon::parse($date)->toDateString())
            ->all();

        $workedDays = $user->workTimeBookings()
            ->whereBetween('booking_day', [$referenceStart->toDateString(), $referenceEnd->toDateString()])
            ->where('worked_hours', '>', 0)
            ->get(['booking_day', 'worked_hours'])
            ->filter(static fn ($booking): bool => $booking->booking_day->dayOfWeek === $weekday)
            ->reject(static fn ($booking): bool => in_array(
                $booking->booking_day->toDateString(),
                $excludedDates,
                true
            ))
            ->groupBy(static fn ($booking): string => $booking->booking_day->toDateString())
            ->map(static fn ($bookings): int => (int) $bookings->sum('worked_hours'))
            ->filter(static fn (int $minutes): bool => $minutes > 0);

        $bookingDates = $workedDays->keys()->all();

        $user->individualTimes()
            ->individualByDateRange($referenceStart->toDateString(), $referenceEnd->toDateString())
            ->get(['start_date', 'end_date', 'working_time_minutes'])
            ->each(function ($individualTime) use (
                $workedDays,
                $bookingDates,
                $excludedDates,
                $referenceStart,
                $referenceEnd,
                $weekday
            ): void {
                $individualDates = Carbon::parse($individualTime->start_date)
                    ->startOfDay()
                    ->daysUntil(Carbon::parse($individualTime->end_date)->startOfDay());

                foreach ($individualDates as $individualDate) {
                    $date = $individualDate->toDateString();

                    if (
                        $individualDate->dayOfWeek !== $weekday
                        || $individualDate->lt($referenceStart)
                        || $individualDate->gt($referenceEnd)
                        || in_array($date, $excludedDates, true)
                        || in_array($date, $bookingDates, true)
                    ) {
                        continue;
                    }

                    $workedDays->put(
                        $date,
                        (int) $workedDays->get($date, 0) + max(0, (int) ($individualTime->working_time_minutes ?? 0))
                    );
                }
            });

        $workedDays = $workedDays->filter(static fn (int $minutes): bool => $minutes > 0);

        $this->averageCache[$cacheKey] = $workedDays->isEmpty()
            ? null
            : max(0, (int) round($workedDays->average()));

        return $this->averageCache[$cacheKey] ?? max(0, $fallbackMinutes);
    }

    /**
     * Minderung (Minuten) für einen Ausgleichstag mit Wert $dayValue (0.5/1.0):
     * im Dreimonatsmodus anhand des Wochentagsdurchschnitts, sonst anhand des Tagessolls.
     * $useThreeMonthAverage = null -> Vertragsflag (Zuweisung vor Vorlage) lesen.
     */
    public function reductionMinutesFor(
        User $user,
        Carbon $targetDate,
        float $dayValue,
        int $contractualDailyTargetMinutes,
        ?bool $useThreeMonthAverage = null
    ): int {
        $useThreeMonthAverage ??= $this->usesThreeMonthAverage($user);
        $referenceMinutes = $useThreeMonthAverage
            ? $this->averageMinutesFor($user, $targetDate, $contractualDailyTargetMinutes)
            : $contractualDailyTargetMinutes;

        return min(
            $contractualDailyTargetMinutes,
            max(0, (int) round($referenceMinutes * $dayValue))
        );
    }

    public function usesThreeMonthAverage(User $user): bool
    {
        return $this->contractSettings->bool($user, 'use_three_month_average_for_target_reduction', false);
    }

    /**
     * @return array{start: string, end: string}
     */
    public function referencePeriodFor(Carbon $targetDate): array
    {
        $end = $targetDate->copy()->subMonthNoOverflow()->endOfMonth();

        return [
            'start' => $end->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    public function flush(): void
    {
        $this->averageCache = [];
    }
}
