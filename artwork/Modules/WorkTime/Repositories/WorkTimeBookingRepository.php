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
     * Tagesbuchung schreiben und Saldo um das Delta zur bisherigen Tageszeile ändern – Delta INNERHALB der
     * Transaktion nach Sperre der User-Zeile ermitteln. Sonst rechnen zwei gleichzeitige Läufe (Nachtlauf über
     * Mitternacht + „Tag neu buchen“, zwei Tabs) gegen denselben Altwert und buchen das Delta doppelt.
     * Die Sperre zuerst auf die User-Zeile verhindert zudem den S→X-Deadlock aus FK-Insert + increment.
     *
     * @param array<string, mixed> $bookingData muss work_time_balance_change enthalten
     * @return int gebuchtes Delta
     */
    public function bookDailyWithLockedBalance(User $user, Carbon $date, array $bookingData): int
    {
        return DB::transaction(function () use ($user, $date, $bookingData): int {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $previous = $this->getPreviousBooking($user, $date);
            $delta = (int) $bookingData['work_time_balance_change']
                - (int) ($previous?->work_time_balance_change ?? 0);

            // Bestehende Zeile (bei Altdaten-Duplikaten die älteste, wie in der Anzeige) gezielt aktualisieren
            $attributes = array_merge($bookingData, ['booking_weekday' => $date->dayOfWeek]);
            if ($previous !== null) {
                $previous->update($attributes);
            } else {
                $user->workTimeBookings()->create(
                    array_merge($attributes, ['booking_day' => $date->toDateString()])
                );
            }
            if ($delta !== 0) {
                $this->updateUserBalance($user, $delta);
            }

            return $delta;
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
            ->orderBy('id') // bei Altdaten-Duplikaten immer dieselbe Zeile wie die Anzeige
            ->first();
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
            // Gleiche Sperrreihenfolge wie die Tagesbuchung (User zuerst) – kein Deadlock bei parallelem Lauf
            User::query()->whereKey($user->id)->lockForUpdate()->first();
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
