<?php

namespace Artwork\Modules\Calendar\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Kalendertage, die ein Termin belegt. Bewusst Tage statt 24-h-Schritte ab der Startuhrzeit —
 * sonst fiele der letzte Tag weg, sobald die Enduhrzeit vor der Startuhrzeit liegt
 * (24.10. 15:00 – 26.10. 14:00 ergab nur 24. und 25.10.). Ein Ende exakt um 00:00 belegt den
 * Folgetag nicht (22:00 – 00:00 bleibt eintägig). Spiegel im Frontend: getEventDaysInRange()
 * in calendarDateUtils.js.
 */
final class EventCalendarDays
{
    public static function between(string $start, string $end): CarbonPeriod
    {
        $eventStart = Carbon::parse($start);
        $eventEnd = Carbon::parse($end);

        if ($eventEnd->gt($eventStart) && $eventEnd->isStartOfDay()) {
            $eventEnd->subMinute();
        }

        $firstDay = $eventStart->copy()->startOfDay();
        $lastDay = $eventEnd->copy()->startOfDay();

        // Defekte Altdaten (Ende vor Start, z.B. 22:00–00:00 am selben Tag) ergäben eine
        // leere Periode — mindestens am Starttag anzeigen.
        if ($lastDay->lt($firstDay)) {
            $lastDay = $firstDay;
        }

        return CarbonPeriod::create($firstDay, $lastDay);
    }
}
