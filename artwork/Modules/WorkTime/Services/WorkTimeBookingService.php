<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;

/**
 * Nächtliche Buchung des Arbeitszeitkontos (ein Datensatz je User und Tag).
 *
 * Soll und Ist kommen ausschließlich aus dem WorkTimeCalculationService (Sondertage,
 * Ersatzfreie Tage, Krank/Urlaub, Dreimonatsdurchschnitt). Hier bleibt nur die
 * Nachtstunden-Ermittlung und die atomare Buchung inkl. Saldo-Delta.
 */
class WorkTimeBookingService
{
    public function __construct(
        protected GeneralSettings $settings,
        protected WorkTimeBookingRepository $repository,
        protected WorkingHourCacheService $workingHourCacheService,
        protected WorkTimeCalculationService $workTimeCalculationService,
    ) {
    }

    public function calculateDailyWorkingHours(): void
    {
        $this->refreshWorkTimeActivations();

        $users = $this->repository->getWorkShiftUsers();
        $today = now()->startOfDay();
        $weekdayIndex = $today->dayOfWeek;

        foreach ($users as $user) {
            $workTimeEntry = $this->getActiveUserWorkTime($user);

            if (!$workTimeEntry) {
                continue;
            }

            // Bestehende Buchung des Tages (Re-Run) darf nicht als Ist zurückfließen -> use_bookings=false
            $context = $this->workTimeCalculationService->buildContext($user, $today, $today, [
                'use_bookings' => false,
            ]);
            $breakdown = $this->workTimeCalculationService->dayBreakdown($user, $today, $context);

            $wantedMinutes = (int) $breakdown['target'];
            $workedMinutes = (int) $breakdown['actual'];
            $nightMinutes = $breakdown['is_sick'] && $breakdown['sick_factor'] >= 1.0
                ? 0 // Krankheit zählt keine Nachtzeit
                : $this->calculateNightMinutes($today, $user);

            $workTimeBalanceChange = $this->calculateWorkTimeBalanceChange($workedMinutes, $wantedMinutes);

            $previousBooking = $this->repository->getPreviousBooking($user, $today, $weekdayIndex);
            $delta = $previousBooking
                ? $workTimeBalanceChange - $previousBooking->work_time_balance_change
                : $workTimeBalanceChange;

            // Buchung UND Saldo-Anpassung atomar in einer Transaktion (vorher war das
            // Balance-Update separat danach -> bei Worker-Crash dazwischen blieb der Saldo
            // dauerhaft falsch, da der Re-Run wegen vorhandener Buchung delta=0 errechnet).
            $this->repository->storeBookingAndUpdateBalanceInTransaction($user, $today, $weekdayIndex, [
                'name' => "daily_work_time_booking_{$today->toDateString()}",
                'wanted_working_hours' => $wantedMinutes,
                'worked_hours' => $workedMinutes,
                'nightly_working_hours' => $nightMinutes,
                'is_special_day' => (bool) $breakdown['is_special_day'],
                'work_time_balance_change' => $workTimeBalanceChange,
            ], $delta);

            $this->workingHourCacheService->forgetForEntity('user', $user->id);

            // Rebuild overtime entries + deadlines (flips expired open entries to "payable").
            app(OvertimeService::class)->recomputeForUser($user);
        }
    }

    /**
     * Nachtminuten (Schichten + Individualzeiten) im Nachtfenster der GeneralSettings.
     */
    private function calculateNightMinutes(Carbon $day, User $user): int
    {
        $night = 0;

        $nightStartTime = $this->settings->start_night_time;
        $nightEndTime = $this->settings->end_night_time;

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay()->addSecond();

        $night1Start = $day->copy()->setTimeFromTimeString($nightStartTime);
        $night1End = $day->copy()->endOfDay();

        $night2Start = $day->copy()->addDay()->startOfDay();
        $night2End = $day->copy()->addDay()->setTimeFromTimeString($nightEndTime);

        $overlap = static function (Carbon $start, Carbon $end) use (
            $dayStart,
            $dayEnd,
            $night1Start,
            $night1End,
            $night2Start,
            $night2End
        ): int {
            $workStart = max($start, $dayStart);
            $workEnd = min($end, $dayEnd);
            if (!$workStart->lt($workEnd)) {
                return 0;
            }

            $minutes = 0;
            $nightOverlap1Start = max($workStart, $night1Start);
            $nightOverlap1End = min($workEnd, $night1End);
            if ($nightOverlap1Start->lt($nightOverlap1End)) {
                $minutes += $nightOverlap1Start->diffInMinutes($nightOverlap1End);
            }

            $nightOverlap2Start = max($workStart, $night2Start);
            $nightOverlap2End = min($workEnd, $night2End);
            if ($nightOverlap2Start->lt($nightOverlap2End)) {
                $minutes += $nightOverlap2Start->diffInMinutes($nightOverlap2End);
            }

            return $minutes;
        };

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;
            if (!$pivot?->start_date || !$pivot?->start_time || !$pivot?->end_date || !$pivot?->end_time) {
                continue;
            }

            $start = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $end = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));
            $night += $overlap($start, $end);
        }

        foreach ($user->individualTimes as $individualTime) {
            if (!in_array($day->toDateString(), $individualTime->days_of_individual_time ?? [], true)) {
                continue;
            }
            if ($individualTime->start_time && $individualTime->end_time && !$individualTime->full_day) {
                $start = Carbon::parse($individualTime->start_date . ' ' . $individualTime->start_time);
                $end = Carbon::parse($individualTime->end_date . ' ' . $individualTime->end_time);
                $night += $overlap($start, $end);
            }
        }

        return (int) round($night);
    }

    private function calculateWorkTimeBalanceChange(int $workedHours, int $wantedWorkHours): int
    {
        return $workedHours - $wantedWorkHours;
    }

    private function getActiveUserWorkTime(User $user): ?UserWorkTime
    {
        return $user->getCurrentWorkTime(); // oder ->getValidWorkTime();
    }

    public function refreshWorkTimeActivations(): void
    {
        UserWorkTime::query()
            ->whereDate('valid_from', '<=', now())
            ->where(function ($q): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->update(['is_active' => true]);

        UserWorkTime::query()
            ->where(function ($q): void {
                $q->whereDate('valid_from', '>', now())->orWhereDate('valid_until', '<', now());
            })
            ->update(['is_active' => false]);
    }
}
