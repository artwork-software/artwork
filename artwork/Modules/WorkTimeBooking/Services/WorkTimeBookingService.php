<?php

namespace Artwork\Modules\WorkTimeBooking\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

class WorkTimeBookingService
{

    public function __construct(
        protected GeneralSettings $settings,
    ) {
    }

    /**
     * Calculates and stores daily working hours for all users
     * who are allowed to work shifts. It considers planned working
     * hours based on weekday and actual worked minutes, including
     * night hours.
     *
     * @return void
     */
    public function calculateDailyWorkingHours(): void
    {
        $users = User::where('can_work_shifts', true)->with(['workTime', 'shifts'])->get();
        $today = now()->startOfDay();
        $weekdayIndex = $today->dayOfWeek;
        $weekdayName = $this->getWeekdayName($weekdayIndex);

        foreach ($users as $user) {
            $wantedMinutes = $this->getPlannedWorkingMinutes($user, $weekdayName);

            $isHoliday = $this->isHoliday($today);

            if ($isHoliday) {
                $wantedMinutes = 0; // No planned working hours on holidays
            }

            $workedTimes = $this->calculateShiftMinutes($today, $user);
            $workTimeBalanceChange = $this->calculateWorkTimeBalanceChange(
                $workedTimes['total'],
                $wantedMinutes
            );

            $previousBooking = $user->workTimeBookings()
                ->where('booking_day', $today)
                ->where('booking_weekday', $weekdayIndex)
                ->first();

            if ($previousBooking) {
                $delta = $workTimeBalanceChange - $previousBooking->work_time_balance_change;
            } else {
                $delta = $workTimeBalanceChange;
            }

            $user->workTimeBookings()->updateOrCreate(
                ['booking_day' => $today, 'booking_weekday' => $weekdayIndex],
                [
                    'name' => "daily_work_time_booking_{$today->toDateString()}",
                    'wanted_working_hours' => $wantedMinutes,
                    'worked_hours' => $workedTimes['total'],
                    'nightly_working_hours' => $workedTimes['night'],
                    'is_special_day' => false,
                    'work_time_balance_change' => $workTimeBalanceChange
                ]
            );

            if ($delta !== 0) {
                $user->update([
                    'work_time_balance' => $user->work_time_balance + $delta
                ]);
            }
        }
    }

    /**
     * Returns the weekday name (e.g. 'monday') for a given numeric day index.
     *
     * @param int $day Day index (0 = Sunday, 6 = Saturday)
     * @return string
     */
    private function getWeekdayName(int $day): string
    {
        return [
            0 => 'sunday', 1 => 'monday', 2 => 'tuesday',
            3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'
        ][$day] ?? 'unknown';
    }

    /**
     * Returns the number of planned working minutes for a given user on a weekday.
     *
     * @param User $user
     * @param string $weekday Weekday name (e.g. 'monday')
     * @return int Planned working minutes
     */
    private function getPlannedWorkingMinutes(User $user, string $weekday): int
    {
        $plannedTime = $user->workTime[$weekday] ?? null;
        return $plannedTime ? now()->startOfDay()->diffInMinutes($plannedTime) : 0;
    }

    /**
     * Calculates total and nightly shift minutes for a user on a specific day.
     *
     * @param Carbon $day Date to calculate for (start of day)
     * @param User $user The user whose shifts will be analyzed
     * @return array<string, int> ['total' => totalMinutes, 'night' => nightMinutes]
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
            $break = (int)($pivot->break_minutes ?? 0);

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


    /**
     * Calculates the change in work time balance based on worked hours and wanted work hours.
     *
     * @param int $workedHours Total worked hours
     * @param int $wantedWorkHours Planned working hours
     * @return int Change in work time balance
     */
    private function calculateWorkTimeBalanceChange(int $workedHours, int $wantedWorkHours): int
    {
        return $workedHours - $wantedWorkHours;
    }

    private function isHoliday(Carbon $day): bool
    {
        $formattedDate = $day->toDateString();
        $monthDay = $day->format('m-d');

        return Holiday::where(function ($query) use ($formattedDate, $monthDay): void {
            $query->where(function ($q) use ($formattedDate): void {
                $q->where('yearly', false)
                    ->whereDate('date', $formattedDate);
            })->orWhere(function ($q) use ($monthDay): void {
                $q->where('yearly', true)
                    ->whereRaw("DATE_FORMAT(date, '%m-%d') = ?", [$monthDay]);
            });
        })
            ->where('treatAsSpecialDay', true)
            ->exists();
    }
}
