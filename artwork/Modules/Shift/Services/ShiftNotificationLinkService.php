<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Services\HelperService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Baut die Deep-Links für Schicht-Benachrichtigungen.
 *
 * Mitarbeitende landen immer in ihrem eigenen Einsatzplan (Route user.operationPlan),
 * Planer*innen im Dienstplan (Route shifts.plan). Beide Ziele übernehmen start_date/end_date
 * aus der URL in den jeweils gespeicherten Zeitraum-Filter, damit der Link die betroffene
 * Woche öffnet (UserController::operationPlan, EventController::viewShiftPlan).
 */
final class ShiftNotificationLinkService
{
    /**
     * Montag–Sonntag der ISO-Woche, in der $date liegt.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function weekRangeForDate(Carbon|string|null $date): array
    {
        $day = $date instanceof Carbon ? $date->copy() : Carbon::parse($date ?: 'now');

        return [
            $day->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay(),
            $day->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay(),
        ];
    }

    /**
     * Montag–Sonntag einer ISO-Kalenderwoche. Eine nicht existierende KW (z. B. 53 in einem
     * 52-Wochen-Jahr) fällt auf die letzte KW des Jahres zurück, damit der Link nicht still in
     * KW 1 des Folgejahres landet (Carbon::setISODate rollt sonst über).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function weekRangeForCalendarWeek(int $week, int $year): array
    {
        $week = max(1, min($week, HelperService::isoWeeksInYear($year)));
        $carbon = Carbon::now()->setISODate($year, $week);

        return [
            $carbon->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay(),
            $carbon->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay(),
        ];
    }

    /**
     * Eigener Einsatzplan der Person, geöffnet auf den übergebenen Zeitraum.
     */
    public static function ownOperationPlan(User|int $user, Carbon $start, Carbon $end): string
    {
        return route('user.operationPlan', [
            'user' => $user instanceof User ? $user->id : $user,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);
    }

    /**
     * Eigener Einsatzplan, geöffnet auf die Woche des übergebenen Datums.
     */
    public static function ownOperationPlanForDate(User|int $user, Carbon|string|null $date): string
    {
        [$start, $end] = self::weekRangeForDate($date);

        return self::ownOperationPlan($user, $start, $end);
    }

    /**
     * Eigener Einsatzplan, geöffnet auf eine Kalenderwoche.
     */
    public static function ownOperationPlanForCalendarWeek(User|int $user, int $week, int $year): string
    {
        [$start, $end] = self::weekRangeForCalendarWeek($week, $year);

        return self::ownOperationPlan($user, $start, $end);
    }

    /**
     * Dienstplan (Planer*innen-Sicht), geöffnet auf den übergebenen Zeitraum.
     */
    public static function shiftPlan(Carbon $start, Carbon $end): string
    {
        return route('shifts.plan', [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);
    }

    /**
     * Dienstplan, geöffnet auf die Woche des übergebenen Datums.
     */
    public static function shiftPlanForDate(Carbon|string|null $date): string
    {
        [$start, $end] = self::weekRangeForDate($date);

        return self::shiftPlan($start, $end);
    }
}
