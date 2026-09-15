<?php

namespace Artwork\Modules\AiAssistant\Services;

use Carbon\CarbonImmutable;

/**
 * Freie Zeitfenster je Raum berechnen (core-api-events-rooms; Konzept B1 §5).
 *
 * Reine Funktion ohne Datenbank: Eingabe sind die Belegungen je Raum, das Tagesfenster, die
 * Mindestdauer und optional die erlaubten Wochentage. Alles, was mit Sichtbarkeit zu tun hat,
 * passiert davor — hier wird nur gerechnet, damit sich die Randfaelle (Tagesgrenzen,
 * Ueberlappungen, exakte Mindestdauer) direkt pruefen lassen.
 */
final class FreeSlotFinder
{
    /** Konzept B1 §5: hoechstens 20 Slots, laengste zuerst. */
    public const MAX_SLOTS = 20;

    /**
     * @param array<int, list<array{start: CarbonImmutable, end: CarbonImmutable}>> $occupancyByRoom
     *        Belegungen je Raum-ID; Reihenfolge egal, Ueberlappungen erlaubt.
     * @param list<int> $weekdays ISO-Wochentage (1 = Montag); leer = alle
     * @param array{from: string, to: string} $dayWindow Tagesfenster als "HH:MM"
     * @return list<array{room_id: int, start: CarbonImmutable, end: CarbonImmutable, hours: float}>
     */
    public function find(
        array $occupancyByRoom,
        CarbonImmutable $from,
        CarbonImmutable $to,
        float $minHours,
        array $weekdays = [],
        array $dayWindow = ['from' => '08:00', 'to' => '22:00'],
        int $limit = self::MAX_SLOTS,
    ): array {
        $slots = [];

        foreach ($occupancyByRoom as $roomId => $occupancy) {
            $merged = $this->merge($occupancy);

            foreach ($this->days($from, $to, $weekdays) as $day) {
                $windowStart = $this->atTime($day, $dayWindow['from']);
                $windowEnd = $this->atTime($day, $dayWindow['to']);

                if (!$windowEnd->greaterThan($windowStart)) {
                    continue;
                }

                foreach ($this->gaps($merged, $windowStart, $windowEnd) as $gap) {
                    $hours = $this->hours($gap['start'], $gap['end']);

                    // Fliesskomma: eine Luecke von exakt der Mindestdauer zaehlt noch mit.
                    if ($hours + 0.0001 < $minHours) {
                        continue;
                    }

                    $slots[] = [
                        'room_id' => (int) $roomId,
                        'start' => $gap['start'],
                        'end' => $gap['end'],
                        'hours' => round($hours, 2),
                    ];
                }
            }
        }

        usort($slots, static function (array $a, array $b): int {
            // Laengste zuerst; bei gleicher Laenge das frueheste Fenster, damit die Ausgabe
            // reproduzierbar ist.
            return [$b['hours'], $a['start']->getTimestamp()] <=> [$a['hours'], $b['start']->getTimestamp()];
        });

        return array_slice($slots, 0, max(0, $limit));
    }

    /**
     * Ueberlappende und aneinandergrenzende Belegungen zusammenfassen.
     *
     * @param list<array{start: CarbonImmutable, end: CarbonImmutable}> $occupancy
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function merge(array $occupancy): array
    {
        $occupancy = array_values(array_filter(
            $occupancy,
            static fn (array $entry): bool => $entry['end']->greaterThan($entry['start'])
        ));

        usort(
            $occupancy,
            static fn (array $a, array $b): int => $a['start']->getTimestamp() <=> $b['start']->getTimestamp()
        );

        $merged = [];

        foreach ($occupancy as $entry) {
            $last = $merged === [] ? null : $merged[array_key_last($merged)];

            if ($last !== null && !$entry['start']->greaterThan($last['end'])) {
                if ($entry['end']->greaterThan($last['end'])) {
                    $merged[array_key_last($merged)]['end'] = $entry['end'];
                }

                continue;
            }

            $merged[] = $entry;
        }

        return $merged;
    }

    /**
     * Luecken innerhalb eines Tagesfensters.
     *
     * @param list<array{start: CarbonImmutable, end: CarbonImmutable}> $merged
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function gaps(array $merged, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        $gaps = [];
        $cursor = $windowStart;

        foreach ($merged as $entry) {
            // Termine ausserhalb des Fensters (auch tagesuebergreifende) werden am Fensterrand
            // beschnitten, statt es zu verlassen.
            $start = $entry['start']->greaterThan($windowStart) ? $entry['start'] : $windowStart;
            $end = $entry['end']->lessThan($windowEnd) ? $entry['end'] : $windowEnd;

            if (!$end->greaterThan($windowStart) || !$start->lessThan($windowEnd)) {
                continue;
            }

            if ($start->greaterThan($cursor)) {
                $gaps[] = ['start' => $cursor, 'end' => $start];
            }

            if ($end->greaterThan($cursor)) {
                $cursor = $end;
            }
        }

        if ($windowEnd->greaterThan($cursor)) {
            $gaps[] = ['start' => $cursor, 'end' => $windowEnd];
        }

        return $gaps;
    }

    /**
     * @param list<int> $weekdays
     * @return list<CarbonImmutable>
     */
    private function days(CarbonImmutable $from, CarbonImmutable $to, array $weekdays): array
    {
        $days = [];
        $day = $from->startOfDay();
        $last = $to->startOfDay();

        while (!$day->greaterThan($last)) {
            if ($weekdays === [] || in_array($day->isoWeekday(), $weekdays, true)) {
                $days[] = $day;
            }

            $day = $day->addDay();
        }

        return $days;
    }

    private function atTime(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return $day->setTime($hour, $minute);
    }

    private function hours(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return ($end->getTimestamp() - $start->getTimestamp()) / 3600;
    }
}
