<?php

namespace Artwork\Core\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class HelperService
{
    /**
     * Anzahl der ISO-Kalenderwochen eines Jahres (52 oder 53; der 28. Dezember liegt immer in der letzten KW).
     */
    public static function isoWeeksInYear(int $year): int
    {
        return (int) Carbon::create($year, 12, 28, 12)->format('W');
    }

    /**
     * Existiert die ISO-KW im Jahr? (KW 53 gibt es nur in Jahren mit 53 Wochen, z. B. 2020 und 2026 — nicht 2025.)
     */
    public static function isoWeekExists(int $week, int $year): bool
    {
        return $week >= 1 && $week <= self::isoWeeksInYear($year);
    }

    /**
     * Montag/Sonntag einer ISO-Kalenderwoche. Eine KW 53 in einem 52-Wochen-Jahr fällt auf die letzte
     * existierende KW des Jahres zurück (statt still in KW 1 des Folgejahres zu rutschen, wie es
     * Carbon::setISODate täte) — für Links/Anzeigen ist die letzte Woche des Jahres die erwartete Nachbarschaft.
     */
    public function getDateRangeByCalendarWeekAndYear(int $week, int $year): array
    {
        if ($week < 1 || $week > 53 || $year < 1970) {
            throw new \InvalidArgumentException('Invalid week or year provided.');
        }

        $week = min($week, self::isoWeeksInYear($year));
        $carbon = Carbon::now()->setISODate($year, $week);

        $startDate = $carbon->copy()->startOfWeek(CarbonInterface::MONDAY);
        $endDate   = $carbon->copy()->endOfWeek(CarbonInterface::SUNDAY);

        // WICHTIG: numerisches Array!
        return [
            $startDate,
            $endDate,
        ];
    }
}
