<?php

namespace Artwork\Modules\WorkTimeBooking\Repositories;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTimeBooking\Models\WorkTimeBooking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkTimeBookingRepository
{
    /**
     * Get all users who are allowed to work shifts.
     *
     * @return Collection<int, User>
     */
    public function getWorkShiftUsers(): Collection
    {
        return User::where('can_work_shifts', true)
            ->with(['workTime', 'shifts'])
            ->get();
    }

    /**
     * Perform user booking + balance update in a transaction.
     *
     * @param User $user
     * @param Carbon $date
     * @param int $weekdayIndex
     * @param array<string, mixed> $bookingData
     * @param int|null $balanceDelta
     * @return void
     */
    public function storeBookingAndUpdateBalanceInTransaction(
        User $user,
        Carbon $date,
        int $weekdayIndex,
        array $bookingData,
        ?int $balanceDelta = null
    ): void {
        DB::transaction(function () use ($user, $date, $weekdayIndex, $bookingData, $balanceDelta): void {
            $this->storeOrUpdateBooking($user, $date, $weekdayIndex, $bookingData);

            if ($balanceDelta !== null && $balanceDelta !== 0) {
                $this->updateUserBalance($user, $balanceDelta);
            }
        });
    }

    /**
     * Fetch the work time booking entry for a specific user on a specific day.
     *
     * @param User $user
     * @param Carbon $date
     * @param int $weekdayIndex
     * @return WorkTimeBooking|null
     */
    public function getPreviousBooking(User $user, Carbon $date, int $weekdayIndex): ?WorkTimeBooking
    {
        return $user->workTimeBookings()
            ->where('booking_day', $date->toDateString())
            ->where('booking_weekday', $weekdayIndex)
            ->first();
    }

    /**
     * Store or update a daily work time booking for a user.
     *
     * @param User $user
     * @param Carbon $date
     * @param int $weekdayIndex
     * @param array<string, mixed> $data
     * @return WorkTimeBooking
     */
    public function storeOrUpdateBooking(User $user, Carbon $date, int $weekdayIndex, array $data): WorkTimeBooking
    {
        return $user->workTimeBookings()->updateOrCreate(
            ['booking_day' => $date->toDateString(), 'booking_weekday' => $weekdayIndex],
            $data
        );
    }

    /**
     * Update a user's work time balance by a delta.
     *
     * @param User $user
     * @param int $delta
     * @return bool
     */
    public function updateUserBalance(User $user, int $delta): bool
    {
        $user->work_time_balance += $delta;
        return $user->save();
    }

    /**
     * Check if a date is a holiday treated as a special day.
     *
     * @param Carbon $day
     * @return bool
     */
    public function isHoliday(Carbon $day): bool
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
        })->where('treatAsSpecialDay', true)->exists();
    }
}
