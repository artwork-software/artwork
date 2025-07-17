<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;

class WorkTimeBookingService
{
    public function __construct(
        protected GeneralSettings $settings,
        protected WorkTimeBookingRepository $repository,
    ) {
    }

    public function calculateDailyWorkingHours(): void
    {
        $this->refreshWorkTimeActivations();

        $users = $this->repository->getWorkShiftUsers();
        $today = now()->startOfDay();
        $weekdayIndex = $today->dayOfWeek;
        $weekdayName = $this->getWeekdayName($weekdayIndex);

        foreach ($users as $user) {
            $workTimeEntry = $this->getActiveUserWorkTime($user);

            if (!$workTimeEntry) {
                continue;
            }

            $pattern = $workTimeEntry->workTimePattern;
            $plannedTime = $pattern
                ? $pattern->{$weekdayName}
                : $workTimeEntry->{$weekdayName};

            $wantedMinutes = $plannedTime
                ? $today->diffInMinutes($plannedTime)
                : 0;

            if ($this->repository->isHoliday($today)) {
                $wantedMinutes = 0;
            }

            $workedTimes = $this->calculateShiftMinutes($today, $user);
            $workTimeBalanceChange = $this->calculateWorkTimeBalanceChange(
                $workedTimes['total'],
                $wantedMinutes
            );

            $previousBooking = $this->repository->getPreviousBooking($user, $today, $weekdayIndex);
            $delta = $previousBooking
                ? $workTimeBalanceChange - $previousBooking->work_time_balance_change
                : $workTimeBalanceChange;

            $this->repository->storeBookingAndUpdateBalanceInTransaction($user, $today, $weekdayIndex, [
                'name' => "daily_work_time_booking_{$today->toDateString()}",
                'wanted_working_hours' => $wantedMinutes,
                'worked_hours' => $workedTimes['total'],
                'nightly_working_hours' => $workedTimes['night'],
                'is_special_day' => false,
                'work_time_balance_change' => $workTimeBalanceChange,
            ]);

            if ($delta !== 0) {
                $this->repository->updateUserBalance($user, $delta);
            }
        }
    }


    /*public function calculateDailyWorkingHours(): void
    {
        $users = $this->repository->getWorkShiftUsers();
        $today = now()->startOfDay();
        $weekdayIndex = $today->dayOfWeek;
        $weekdayName = $this->getWeekdayName($weekdayIndex);

        foreach ($users as $user) {
            $wantedMinutes = $this->getPlannedWorkingMinutes($user, $weekdayName);

            if ($this->repository->isHoliday($today)) {
                $wantedMinutes = 0;
            }

            $workedTimes = $this->calculateShiftMinutes($today, $user);
            $workTimeBalanceChange = $this->calculateWorkTimeBalanceChange(
                $workedTimes['total'],
                $wantedMinutes
            );

            $previousBooking = $this->repository->getPreviousBooking($user, $today, $weekdayIndex);
            $delta = $previousBooking
                ? $workTimeBalanceChange - $previousBooking->work_time_balance_change
                : $workTimeBalanceChange;

            $this->repository->storeBookingAndUpdateBalanceInTransaction($user, $today, $weekdayIndex, [
                'name' => "daily_work_time_booking_{$today->toDateString()}",
                'wanted_working_hours' => $wantedMinutes,
                'worked_hours' => $workedTimes['total'],
                'nightly_working_hours' => $workedTimes['night'],
                'is_special_day' => false,
                'work_time_balance_change' => $workTimeBalanceChange,
            ]);

            if ($delta !== 0) {
                $this->repository->updateUserBalance($user, $delta);
            }
        }
    }*/

    private function getWeekdayName(int $day): string
    {
        return [
            0 => 'sunday', 1 => 'monday', 2 => 'tuesday',
            3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'
        ][$day] ?? 'unknown';
    }

    private function getPlannedWorkingMinutes(User $user, string $weekday): int
    {
        $plannedTime = $user->workTime[$weekday] ?? null;
        return $plannedTime ? now()->startOfDay()->diffInMinutes($plannedTime) : 0;
    }

    /**
     * @param Carbon $day
     * @param User $user
     * @return int[]
     */
    private function calculateShiftMinutes(Carbon $day, User $user): array
    {
        $total = 0;
        $night = 0;

        $nightStartTime = $this->settings->start_night_time;
        $nightEndTime = $this->settings->end_night_time;

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay()->addSecond();

        $night1Start = $day->copy()->setTimeFromTimeString($nightStartTime);
        $night1End = $day->copy()->endOfDay();

        $night2Start = $day->copy()->addDay()->startOfDay();
        $night2End = $day->copy()->addDay()->setTimeFromTimeString($nightEndTime);

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;

            $start = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $end = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));
            $break = (int)($shift->break_minutes ?? 0);

            $workStart = max($start, $dayStart);
            $workEnd = min($end, $dayEnd);

            if ($workStart->lt($workEnd)) {
                $duration = $workStart->diffInMinutes($workEnd) - $break;
                $total += max(0, $duration);

                $nightOverlap1Start = max($workStart, $night1Start);
                $nightOverlap1End = min($workEnd, $night1End);
                if ($nightOverlap1Start->lt($nightOverlap1End)) {
                    $night += $nightOverlap1Start->diffInMinutes($nightOverlap1End);
                }

                $nightOverlap2Start = max($workStart, $night2Start);
                $nightOverlap2End = min($workEnd, $night2End);
                if ($nightOverlap2Start->lt($nightOverlap2End)) {
                    $night += $nightOverlap2Start->diffInMinutes($nightOverlap2End);
                }
            }
        }

        return [
            'total' => (int) round($total),
            'night' => (int) round($night)
        ];
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
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->update(['is_active' => true]);

        UserWorkTime::query()
            ->where(function ($q) {
                $q->whereDate('valid_from', '>', now())->orWhereDate('valid_until', '<', now());
            })
            ->update(['is_active' => false]);
    }
}
