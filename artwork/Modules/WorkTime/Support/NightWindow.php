<?php

namespace Artwork\Modules\WorkTime\Support;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Carbon\Carbon;

/**
 * Nachtfenster der GeneralSettings (start_night_time–end_night_time, Standard 22:00–06:00) als EINE
 * gemeinsame Rechenbasis für Nachtminuten — genutzt von der nächtlichen Buchung
 * (WorkTimeBookingService::calculateNightMinutes) und der Regelprüfung NightWorkMaxHoursCheck, damit
 * beide dieselben Minuten zählen.
 *
 * minutesWithin() liefert die Minuten eines Zeitraums [start, end] innerhalb des Fensters — über alle
 * berührten Kalendertage, ein über Mitternacht laufendes Fenster (22:00–06:00) wird je Tag in die
 * Abschnitte 00:00–Ende und Beginn–24:00 zerlegt. WELCHEM Tag ein Zeitraum zugerechnet wird (Schicht
 * ganz dem Starttag, individuelle Zeit je Kalendertag zugeschnitten), entscheidet der Aufrufer durch
 * den übergebenen Zeitraum.
 */
final class NightWindow
{
    public const DEFAULT_START = '22:00';
    public const DEFAULT_END = '06:00';

    /**
     * @param string $start 'HH:MM'
     * @param string $end 'HH:MM'
     */
    public function __construct(
        public readonly string $start,
        public readonly string $end,
    ) {
    }

    /**
     * Aus den GeneralSettings; unbrauchbare oder leere Werte fallen auf 22:00–06:00 zurück.
     */
    public static function fromSettings(?GeneralSettings $settings = null): self
    {
        $settings ??= app(GeneralSettings::class);

        return new self(
            self::normalize($settings->start_night_time ?? null, self::DEFAULT_START),
            self::normalize($settings->end_night_time ?? null, self::DEFAULT_END),
        );
    }

    private static function normalize(mixed $value, string $default): string
    {
        $time = substr((string) $value, 0, 5);

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : $default;
    }

    /**
     * Anzeige 'HH:MM–HH:MM' (z. B. für Verstoßdaten).
     */
    public function label(): string
    {
        return $this->start . '–' . $this->end;
    }

    /**
     * Minuten des Zeitraums [$start, $end] innerhalb des Nachtfensters (brutto, ohne Pausenabzug),
     * über alle berührten Kalendertage. Leere oder verdrehte Zeiträume ergeben 0.
     */
    public function minutesWithin(Carbon $start, Carbon $end): int
    {
        if ($start->gte($end)) {
            return 0;
        }

        $seconds = 0;
        $day = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();
        while ($day->lte($lastDay)) {
            foreach ($this->segmentsOfDay($day) as [$segmentStart, $segmentEnd]) {
                $overlapStart = $start->greaterThan($segmentStart) ? $start : $segmentStart;
                $overlapEnd = $end->lessThan($segmentEnd) ? $end : $segmentEnd;
                if ($overlapStart->lt($overlapEnd)) {
                    $seconds += (int) $overlapStart->diffInSeconds($overlapEnd);
                }
            }
            $day->addDay();
        }

        return intdiv($seconds, 60);
    }

    /**
     * Nachtfenster-Abschnitte eines Kalendertags: ohne Mitternacht ein Abschnitt (z. B. 20:00–23:00),
     * über Mitternacht früher Morgen (00:00–Ende) und Abend (Beginn–24:00).
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    public function segmentsOfDay(Carbon $day): array
    {
        $dayStart = $day->copy()->startOfDay();
        $startAt = $dayStart->copy()->setTimeFromTimeString($this->start);
        $endAt = $dayStart->copy()->setTimeFromTimeString($this->end);
        if ($startAt->lt($endAt)) {
            return [[$startAt, $endAt]];
        }

        return [
            [$dayStart->copy(), $endAt],
            [$startAt, $dayStart->copy()->addDay()],
        ];
    }
}
