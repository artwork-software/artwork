<?php

namespace Artwork\Modules\WorkTime\Repositories;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
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
            ->with(['workTimes', 'shifts', 'individualTimes'])
            ->get();
    }

    /**
     * Eindeutiger Name der nächtlichen Tagesbuchung (seit Einführung unverändert).
     */
    public static function dailyBookingName(Carbon $date): string
    {
        return 'daily_work_time_booking_' . $date->toDateString();
    }

    /**
     * Tagesbuchung + Saldo-Delta atomar in einer Transaktion.
     *
     * @param array<string, mixed> $bookingData
     */
    public function storeDailyBookingAndUpdateBalanceInTransaction(
        User $user,
        Carbon $date,
        int $weekdayIndex,
        array $bookingData,
        ?int $balanceDelta = null
    ): void {
        DB::transaction(function () use ($user, $date, $weekdayIndex, $bookingData, $balanceDelta): void {
            $this->storeOrUpdateDailyBooking($user, $date, $weekdayIndex, $bookingData);

            if ($balanceDelta !== null && $balanceDelta !== 0) {
                $this->updateUserBalance($user, $balanceDelta);
            }
        });
    }

    /**
     * Nächtliche Tagesbuchung einer Person für einen Tag. Gesucht wird über den Namen: Korrektur- und
     * manuelle Buchungen desselben Tages sind eigene Zeilen und dürfen hier nicht gefunden werden
     * (sonst verrechnet der Re-Run ihren Betrag und überschreibt die Zeile).
     */
    public function getPreviousBooking(User $user, Carbon $date): ?WorkTimeBooking
    {
        return $user->workTimeBookings()
            ->where('booking_day', $date->toDateString())
            ->where('name', self::dailyBookingName($date))
            ->first();
    }

    /**
     * Legt die nächtliche Tagesbuchung an oder aktualisiert sie (Re-Run), Treffer nur über den Namen.
     *
     * @param array<string, mixed> $data
     */
    public function storeOrUpdateDailyBooking(
        User $user,
        Carbon $date,
        int $weekdayIndex,
        array $data
    ): WorkTimeBooking {
        return $user->workTimeBookings()->updateOrCreate(
            ['booking_day' => $date->toDateString(), 'name' => self::dailyBookingName($date)],
            array_merge($data, ['booking_weekday' => $weekdayIndex])
        );
    }

    /**
     * Korrektur-/Einzelbuchung immer als eigene Zeile, inkl. Saldo-Delta, atomar.
     *
     * @param array<string, mixed> $data
     */
    public function createBookingAndUpdateBalanceInTransaction(
        User $user,
        array $data,
        int $balanceDelta
    ): WorkTimeBooking {
        return DB::transaction(function () use ($user, $data, $balanceDelta): WorkTimeBooking {
            $booking = $user->workTimeBookings()->create($data);

            if ($balanceDelta !== 0) {
                $this->updateUserBalance($user, $balanceDelta);
            }

            return $booking;
        });
    }

    /**
     * Atomar in der Datenbank (work_time_balance = work_time_balance + delta): Nächtliche Buchung, manuelle
     * Buchung und Auszahlung arbeiten mit unterschiedlich alten User-Models und überschrieben sich sonst.
     */
    public function updateUserBalance(User $user, int $delta): bool
    {
        $user->increment('work_time_balance', $delta);

        return true;
    }

    /**
     * Sondertag-Prüfung (Flag, mehrtägig, jährlich) über den zentralen SpecialDayService.
     */
    public function isHoliday(Carbon $day): bool
    {
        return app(\Artwork\Modules\Holidays\Services\SpecialDayService::class)->isSpecialDay($day);
    }
}
