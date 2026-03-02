<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kalender</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            font-size: 8px;
            color: #111;
            -webkit-font-smoothing: antialiased;
        }

        .page { page-break-after: always; padding: 16px; }
        .page:last-child { page-break-after: auto; }

        /* HEADER ------------------------------------------------------*/
        .header-table {
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 6px;
            font-size: 8px;
            line-height: 1.3;
            border: none;
            border-collapse: collapse;
        }

        .header-table td { vertical-align: top; padding: 0; border: none; }
        .header-left { width: 60%; font-size: 8px; color: #111; }
        .header-right { width: 40%; text-align: right; font-size: 7px; color: #71717a; line-height: 1.4; }

        .title { font-size: 12px; font-weight: 600; color: #0a0a0a; line-height: 1.3; }
        .subtitle { font-size: 8px; color: #52525b; margin-top: 2px; line-height: 1.4; }
        .chunk-info { font-size: 7px; color: #71717a; line-height: 1.4; margin-top: 2px; }

        /* TABLE ------------------------------------------------------*/
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            border: 2px solid #404040;
            font-size: 6px;
            font-weight: 500;
        }

        th, td { word-wrap: break-word; }

        thead th {
            border-bottom: 2px solid #404040;
            border-right: 2px solid #404040;
            background: #f9fafb;
            vertical-align: top;
            font-size: 6px;
            font-weight: 500;
        }

        .th-room-head {
            font-weight: 500;
            font-size: 6px;
            line-height: 1.3;
            text-align: left;
            padding: 4px;
            color: #111827;
            border-right: 2px solid #404040;
        }

        .th-daygroup {
            text-align: center;
            color: #111827;
            border-right: 2px solid #404040;
            line-height: 1.3;
            padding: 4px 2px;
            font-weight: 500;
            font-size: 6px;
        }
        .th-daygroup-meta { font-weight: 500; font-size: 6px; line-height: 1.2; color: #4b5563; }

        .weekend-bg-top { background-color: #f4f4f5; }
        .weekend-bg-cell { background-color: #f4f4f5; }
        .time-col-bg { background-color: #f4f4f5; }

        tbody td {
            border-bottom: 2px solid #404040;
            border-right: 2px solid #404040;
            vertical-align: top;
            padding: 0;
        }

        /* Raumspalte */
        .td-room {
            padding: 2px;
            vertical-align: middle;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
            word-break: break-word;
        }

        /* Zeitspalte */
        .td-time {
            padding: 0;
            vertical-align: top;
            text-align: center;
            color: #4b5563;
            font-weight: 700;
            font-size: 7px;
        }

        .time-wrap { position: relative; width: 100%; overflow: hidden; }
        .time-block {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-size: 7px;
            font-weight: 700;
            color: #4b5563;
        }

        /* Day cell */
        .day-wrap { position: relative; width: 100%; background: #fff; }

        /* Segment Lines */
        .seg-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px solid rgba(64,64,64,0.35);
            pointer-events: none;
            z-index: 1;
        }

        /* Lane Layout */
        .lanes {
            position: relative;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .lane {
            float: left;
            height: 100%;
            position: relative;
            padding: 2px;
            overflow: hidden;
            border-left: 1px solid rgba(64,64,64,0.15);
        }
        .lane:first-child { border-left: none; }

        .lanes:after { content: ""; display: block; clear: both; }

        /* Event ------------------------------------------------------*/
        .event {
            position: absolute;
            left: 0;
            right: 0;
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,0.40);
            border-left-width: 3px;
            overflow: hidden;
            z-index: 10;
        }

        .event-inner { padding: 2px 3px; overflow: hidden; }

        .event-title {
            font-weight: 800;
            font-size: 6px;
            line-height: 1.15;
            word-break: break-word;
            overflow: hidden;
        }
        .event-sub {
            margin-top: 1px;
            font-weight: 600;
            font-size: 5.7px;
            line-height: 1.1;
            opacity: 0.9;
            word-break: break-word;
            overflow: hidden;
        }
        .event-time {
            margin-top: 1px;
            font-weight: 800;
            font-size: 5.6px;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
        }

        .event-compact .event-inner { padding: 1px 2px; }
        .event-compact .event-title { font-size: 5.4px; line-height: 1.1; }
        .event-compact .event-sub   { display: none; }
        .event-compact .event-time  { font-size: 5.2px; line-height: 1.05; }

        .event-supercompact .event-inner { padding: 1px 2px; }
        .event-supercompact .event-title { font-size: 5.1px; line-height: 1.05; }
        .event-supercompact .event-sub   { display: none; }
        .event-supercompact .event-time  { font-size: 5.0px; line-height: 1.0; }

        .abbr { font-weight: 900; margin-right: 2px; }
    </style>
</head>
<body>

@php
    // === Zeitfenster (WICHTIG): Morgens = 06–12, Mittags = 12–18, Abends = 18–24
    $SCHEDULE_START_MIN = 6 * 60;    // 06:00
    $SCHEDULE_END_MIN   = 24 * 60;   // 24:00

    $MORNING_START_MIN  = 6 * 60;
    $MORNING_END_MIN    = 12 * 60;
    $NOON_START_MIN     = 12 * 60;
    $NOON_END_MIN       = 18 * 60;
    $EVENING_START_MIN  = 18 * 60;
    $EVENING_END_MIN    = 24 * 60;

    function __getEventsForRoomAndDay($calendarLookup, $roomId, $dayDisplay) {
        return $calendarLookup[$roomId][$dayDisplay]['events'] ?? [];
    }

    function __parseDayStart(string $dayDisplay): \Illuminate\Support\Carbon {
        return \Illuminate\Support\Carbon::createFromFormat('d.m.Y', $dayDisplay, config('app.timezone'))->startOfDay();
    }
    function __parseDayEnd(string $dayDisplay): \Illuminate\Support\Carbon {
        return \Illuminate\Support\Carbon::createFromFormat('d.m.Y', $dayDisplay, config('app.timezone'))->endOfDay();
    }

    function __minutesSinceMidnight(\Illuminate\Support\Carbon $t): int {
        return ((int)$t->format('H')) * 60 + ((int)$t->format('i'));
    }

    function __floorToHour(int $minutes): int { return (int)(floor($minutes / 60) * 60); }
    function __ceilToHour(int $minutes): int { return (int)(ceil($minutes / 60) * 60); }

    function __slotForMinute(int $min): string {
        // end-boundary inkl. Handling über "min-1" außerhalb
        if ($min < 12*60) return 'morning';
        if ($min < 18*60) return 'noon';
        return 'evening';
    }

    function __slotBoundsMin(string $slot): array {
        if ($slot === 'morning') return [6*60, 12*60];
        if ($slot === 'noon')    return [12*60, 18*60];
        return [18*60, 24*60];
    }

    /**
     * Mappt Minuten (06:00..24:00) -> Y-Pixel innerhalb der Tageshöhe
     * Morgens (06–12) nutzt hMorning, Mittags (12–18) hNoon, Abends (18–24) hEvening
     */
    function __minuteToYSchedule(
        int $min,
        int $hMorning, int $hNoon, int $hEvening
    ): int {
        $min = max(6*60, min(24*60, $min)); // clamp auf 06..24

        // 06–12 (360..720) -> 0..hMorning
        if ($min <= 12*60) {
            $ratio = ($min - 6*60) / (6*60);
            return (int)round($ratio * $hMorning);
        }

        // 12–18 (720..1080) -> hMorning..hMorning+hNoon
        if ($min <= 18*60) {
            $ratio = ($min - 12*60) / (6*60);
            return (int)round($hMorning + ($ratio * $hNoon));
        }

        // 18–24 (1080..1440) -> hMorning+hNoon..sum
        $ratio = ($min - 18*60) / (6*60);
        return (int)round($hMorning + $hNoon + ($ratio * $hEvening));
    }

    /**
     * Baut Segment für einen Tag:
     * - stundenweise (floor/ceil)
     * - innerhalb eines Slots (nur morning/noon/evening) wird Mindesthöhe so umgesetzt,
     *   dass es NICHT in den nächsten Slot läuft -> lieber nach oben schieben.
     */
    function __buildSegmentForDay(
        $event,
        string $dayDisplay,
        int $hMorning, int $hNoon, int $hEvening
    ) {
        $tz = config('app.timezone');

        $dayStart = __parseDayStart($dayDisplay);
        $dayEnd   = __parseDayEnd($dayDisplay);

        $allDay = (bool)($event->allDay ?? false);

        $start = \Illuminate\Support\Carbon::parse($event->start)->timezone($tz);
        $end   = \Illuminate\Support\Carbon::parse($event->end)->timezone($tz);

        // Tagesclip
        if ($allDay) {
            $effStart = $dayStart->copy();
            $effEnd   = $dayEnd->copy();
        } else {
            $effStart = $start->greaterThan($dayStart) ? $start->copy() : $dayStart->copy();
            $effEnd   = $end->lessThan($dayEnd) ? $end->copy() : $dayEnd->copy();
        }

        if ($effEnd->lte($effStart)) return null;

        $isMultiDay = $start->toDateString() !== $end->toDateString();
        $isStartDay = $start->toDateString() === $dayStart->toDateString();
        $isEndDay   = $end->toDateString() === $dayStart->toDateString();

        // Zeitstring
        if ($allDay) {
            $timeString = 'Ganztägig';
        } else {
            if ($isMultiDay) {
                if (!$isStartDay && !$isEndDay) {
                    $timeString = 'Ganztägig';
                } elseif ($isStartDay && !$isEndDay) {
                    $timeString = $start->format('H:i') . '–24:00';
                } elseif (!$isStartDay && $isEndDay) {
                    $timeString = '00:00–' . $end->format('H:i');
                } else {
                    $timeString = $start->format('H:i') . '–' . $end->format('H:i');
                }
            } else {
                $timeString = $start->format('H:i') . '–' . $end->format('H:i');
            }
        }

        // Minuten
        $startMinRaw = $allDay ? 6*60 : __minutesSinceMidnight($effStart);
        $endMinRaw   = $allDay ? 24*60 : __minutesSinceMidnight($effEnd);

        // Clamp auf sichtbaren Schedule 06..24
        // (alles davor wird oben "auf 06" gezogen -> sichtbar, aber nicht nacht-genau)
        $startMin = max(6*60, min(24*60, $startMinRaw));
        $endMin   = max(6*60, min(24*60, $endMinRaw));

        // stundenweise Quantisierung
        $qStart = __floorToHour($startMin);
        $qEnd   = __ceilToHour($endMin);

        // sicherstellen: mind. 1h
        if ($qEnd <= $qStart) $qEnd = min(24*60, $qStart + 60);

        // pixel mapping (Schedule 06..24)
        $hDay = $hMorning + $hNoon + $hEvening;

        $topPxBase    = __minuteToYSchedule($qStart, $hMorning, $hNoon, $hEvening);
        $bottomPxBase = __minuteToYSchedule($qEnd,   $hMorning, $hNoon, $hEvening);

        if ($bottomPxBase <= $topPxBase) $bottomPxBase = min($hDay, $topPxBase + 1);

        // Slot-Erkennung (Ende genau auf Grenze zählt zum vorherigen Slot)
        $slotStart = __slotForMinute($qStart);
        $slotEnd   = __slotForMinute(max(6*60, $qEnd - 1));

        $isSingleSlot = ($slotStart === $slotEnd);

        // Slot-Pixelbounds
        [$slotMinStart, $slotMinEnd] = __slotBoundsMin($slotStart);
        $slotStartPx = __minuteToYSchedule($slotMinStart, $hMorning, $hNoon, $hEvening);
        $slotEndPx   = __minuteToYSchedule($slotMinEnd,   $hMorning, $hNoon, $hEvening);

        $topPx = $topPxBase;
        $bottomPx = $bottomPxBase;

        // Mindesthöhe: genug für 3 Zeilen (Titel + Projekt + Zeit)
        $minHeightPx = 28;

        $heightPx = $bottomPx - $topPx;

        if ($heightPx < $minHeightPx) {
            if ($isSingleSlot) {
                // 1) bevorzugt: bottom beibehalten (endet "richtig"), top nach oben ziehen
                $bottomPx = min($bottomPxBase, $slotEndPx);
                $topPx = $bottomPx - $minHeightPx;

                // 2) wenn top über Slot-Anfang rausläuft -> in Slot clampen, dann nach unten füllen
                if ($topPx < $slotStartPx) {
                    $topPx = $slotStartPx;
                    $bottomPx = $topPx + $minHeightPx;
                }

                // 3) wenn dadurch bottom über Slot-Ende geht -> nach oben schieben
                if ($bottomPx > $slotEndPx) {
                    $bottomPx = $slotEndPx;
                    $topPx = $bottomPx - $minHeightPx;

                    // wenn Mindesthöhe größer als Slot selbst -> Slot komplett füllen
                    if ($topPx < $slotStartPx) {
                        $topPx = $slotStartPx;
                    }
                }

                // final clamp
                $topPx = max($slotStartPx, min($slotEndPx, $topPx));
                $bottomPx = max($slotStartPx, min($slotEndPx, $bottomPx));
                if ($bottomPx <= $topPx) $bottomPx = min($slotEndPx, $topPx + 1);
            } else {
                // Multi-Slot: Mindesthöhe okay, darf über Linien gehen (aber nicht über Tag hinaus)
                $bottomPx = min($hDay, $bottomPxBase);
                $topPx = $bottomPx - $minHeightPx;
                if ($topPx < 0) $topPx = 0;
            }
        } else {
            // auch ohne Mindesthöhe: Single-Slot strikt im Slot halten (kein "Minuten-Drift" über Linie)
            if ($isSingleSlot) {
                // wenn irgendwas (durch Rundung) über Slot-Ende laufen würde -> nach oben schieben
                if ($bottomPx > $slotEndPx) {
                    $shift = $bottomPx - $slotEndPx;
                    $bottomPx -= $shift;
                    $topPx -= $shift;
                }
                // wenn dadurch top < slotStart -> clamp
                if ($topPx < $slotStartPx) {
                    $topPx = $slotStartPx;
                }
                // und bottom clamp
                $bottomPx = min($slotEndPx, max($slotStartPx + 1, $bottomPx));
            }

            // Tag clamp
            $topPx = max(0, min($hDay - 1, $topPx));
            $bottomPx = max($topPx + 1, min($hDay, $bottomPx));
        }

        $heightPx = max(1, $bottomPx - $topPx);

        // Slot-Span (für Kompaktheitslogik)
        $slotSpan = 1;
        if ($qStart < 12*60 && $qEnd > 12*60) $slotSpan++;
        if ($qStart < 18*60 && $qEnd > 18*60) $slotSpan++;

        $abbr      = $event->eventType?->abbreviation ?? '';
        $hexColor  = $event->eventType?->hex_code ?? '#111111';
        $name      = $event->eventName ?? '';
        $projectNm = $event->project->name ?? null;

        $r = hexdec(substr($hexColor, 1, 2));
        $g = hexdec(substr($hexColor, 3, 2));
        $b = hexdec(substr($hexColor, 5, 2));

        // Hintergrund opak (nicht transparent), damit Linien nicht durchscheinen
        // Mische die Event-Farbe mit Weiß für einen hellen, aber undurchsichtigen Hintergrund
        $bgR = (int)round($r * 0.14 + 255 * 0.86);
        $bgG = (int)round($g * 0.14 + 255 * 0.86);
        $bgB = (int)round($b * 0.14 + 255 * 0.86);
        $bgRGBA     = "rgb($bgR,$bgG,$bgB)";
        $borderRGBA = "rgba($r,$g,$b,0.95)";
        $leftRGBA   = "rgba($r,$g,$b,1)";

        return [
            'topPx'     => $topPx,
            'bottomPx'  => $topPx + $heightPx,
            'heightPx'  => $heightPx,
            'slotSpan'  => $slotSpan,
            'abbr'      => $abbr,
            'name'      => $name,
            'project'   => $projectNm,
            'time'      => $timeString,
            'isMulti'   => $isMultiDay,
            'bg'        => $bgRGBA,
            'border'    => $borderRGBA,
            'left'      => $leftRGBA,
            'rgb'       => [$r,$g,$b],
        ];
    }

    // Lane assignment (Pixel-Intervalle => keine Überlappung)
    function __assignLanes(array $segments): array {
        usort($segments, function($a, $b) {
            if ($a['topPx'] !== $b['topPx']) return $a['topPx'] <=> $b['topPx'];
            if ($a['bottomPx'] !== $b['bottomPx']) return $b['bottomPx'] <=> $a['bottomPx'];
            return 0;
        });

        $laneEnds = [];
        $byLane   = [];

        foreach ($segments as $seg) {
            $assigned = null;

            foreach ($laneEnds as $laneIndex => $endPx) {
                if ($endPx <= $seg['topPx']) {
                    $assigned = $laneIndex;
                    break;
                }
            }

            if ($assigned === null) {
                $assigned = count($laneEnds);
                $laneEnds[$assigned] = 0;
            }

            $laneEnds[$assigned] = $seg['bottomPx'];
            $byLane[$assigned][] = $seg;
        }

        ksort($byLane);
        $laneCount = max(1, count($byLane));

        // “gerne 3 parallele Termine” -> ab 3 wird’s compact, ab 4 supercompact
        return [$byLane, $laneCount];
    }

    $renderDayCell = function(array $eventsForDay, string $dayDisplay, int $hMorning, int $hNoon, int $hEvening) {
        $segments = [];
        foreach ($eventsForDay as $event) {
            $seg = __buildSegmentForDay($event, $dayDisplay, $hMorning, $hNoon, $hEvening);
            if ($seg) $segments[] = $seg;
        }

        [$byLane, $laneCount] = __assignLanes($segments);

        $hDay = $hMorning + $hNoon + $hEvening;

        echo '<div class="lanes" style="height: '.$hDay.'px;">';

        $laneWidth = 100 / $laneCount;
        $segBorder = '1px solid rgba(64,64,64,0.35)';

        foreach ($byLane as $laneIndex => $laneSegments) {
            // Lane mit Slot-Trennlinien als border-bottom auf Block-Divs
            echo '<div class="lane" style="width: '.$laneWidth.'%; height: '.$hDay.'px;">';
            echo '<div style="position:absolute;top:0;left:0;right:0;height:'.$hDay.'px;z-index:0;pointer-events:none;">';
            echo '<div style="height:'.$hMorning.'px;border-bottom:'.$segBorder.';"></div>';
            echo '<div style="height:'.$hNoon.'px;border-bottom:'.$segBorder.';"></div>';
            echo '</div>';

            foreach ($laneSegments as $seg) {
                $cls = 'event';
                if ($laneCount >= 4) $cls .= ' event-supercompact';
                elseif ($laneCount === 3) $cls .= ' event-compact';

                $showSubLine = ($laneCount <= 2 && $seg['slotSpan'] >= 2 && !empty($seg['project']));

                echo '<div class="'.$cls.'" style="'
                    .'top: '.$seg['topPx'].'px;'
                    .'height: '.$seg['heightPx'].'px;'
                    .'background-color: '.$seg['bg'].';'
                    .'border-color: '.$seg['border'].';'
                    .'border-left-color: '.$seg['left'].';'
                .'">';

                echo '<div class="event-inner">';

                echo '<div class="event-title">';
                if (!empty($seg['abbr'])) {
                    $rgb = $seg['rgb'];
                    echo '<span class="abbr" style="color: rgba('.$rgb[0].','.$rgb[1].','.$rgb[2].',1);">'.e($seg['abbr']).'</span>';
                    echo '<span style="font-weight:900;">:</span> ';
                }
                echo e($seg['name']);
                echo '</div>';

                if ($showSubLine) {
                    echo '<div class="event-sub">'.e($seg['project']).'</div>';
                }

                echo '<div class="event-time">';
                if (!empty($seg['isMulti'])) {
                    echo '<span style="color:#dc2626;padding-right:2px;font-weight:900;">!</span>';
                }
                echo e($seg['time']);
                echo '</div>';

                echo '</div></div>'; // inner + event
            }

            echo '</div>'; // lane
        }

        echo '</div>'; // lanes
    };

    $allDaysFlat = collect($dayChunks ?? [])->flatten(1);
    $firstDay    = $allDaysFlat->first()['fullDay'] ?? null;
    $lastDay     = $allDaysFlat->last()['fullDay'] ?? null;
@endphp

@foreach($roomChunks as $roomChunkIndex => $roomChunkPage)
    @foreach($dayChunks as $dayChunkIndex => $daysPage)
        <section class="page">
            {{-- HEADER --}}
            <table class="header-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="header-left">
                        <div class="title">{{ $title ?? 'Raumbelegung' }}</div>
                        <div class="subtitle">
                            @if($firstDay && $lastDay)
                                Zeitraum: {{ $firstDay }} – {{ $lastDay }}
                            @endif
                        </div>
                    </td>
                    <td class="header-right">
                        <div class="chunk-info">Erstellt von {{ $created_by }}</div>
                        <div class="chunk-info">Erstellt am {{ \Illuminate\Support\Carbon::now()->format('d.m.Y H:i') }}</div>
                    </td>
                </tr>
            </table>

            {{-- TABELLE --}}
            <table>
                <thead>
                <tr>
                    <th class="th-room-head" style="text-align:center; vertical-align:middle; font-size:9px; font-weight:700;">
                        Raum
                    </th>

                    <th class="th-room-head time-col-bg" style="padding: 1px; font-size: 9px; font-weight: 700; line-height: 1.2; background-color:#f4f4f5; text-align:center; vertical-align:middle; white-space:nowrap;">
                        Zeit
                    </th>

                    @foreach($daysPage as $dayInfo)
                        @php $isWeekend = !empty($dayInfo['isWeekend']); @endphp
                        <th class="th-daygroup {{ $isWeekend ? 'weekend-bg-top' : '' }}"
                            style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }} font-size:9px; line-height:1.3; font-weight:700; padding:4px 2px; text-align:center;"
                        >
                            <div>{{ $dayInfo['dayString'] }} {{ $dayInfo['fullDay'] }}</div>
                            <div class="th-daygroup-meta">KW {{ $dayInfo['weekNumber'] }}</div>
                        </th>
                    @endforeach
                </tr>
                </thead>

                <tbody>
                @foreach($roomChunkPage as $room)
                    @php
                        $roomName = $room->name;

                        $hMorning = (int)($rowHeights[$room->id]['morning'] ?? 36);
                        $hNoon    = (int)($rowHeights[$room->id]['noon'] ?? 36);
                        $hEvening = (int)($rowHeights[$room->id]['evening'] ?? 36);
                        $hDay     = $hMorning + $hNoon + $hEvening;
                    @endphp

                    <tr>
                        {{-- Raum --}}
                        <td class="td-room" style="height: {{ $hDay }}px;">
                            {{ $roomName }}
                        </td>

                        {{-- Zeitspalte (Linien exakt wie Day-Cells) --}}
                        <td class="td-time time-col-bg" style="height: {{ $hDay }}px; background-color:#f4f4f5;">
                            <div class="time-wrap" style="height: {{ $hDay }}px;">
                                <div class="time-block" style="height: {{ $hMorning }}px; border-bottom: 1px solid rgba(64,64,64,0.35);">Morgens</div>
                                <div class="time-block" style="height: {{ $hNoon }}px; border-bottom: 1px solid rgba(64,64,64,0.35);">Mittags</div>
                                <div class="time-block" style="height: {{ $hEvening }}px;">Abends</div>
                            </div>
                        </td>

                        {{-- Tage --}}
                        @foreach($daysPage as $dayInfo)
                            @php
                                $fullDay   = $dayInfo['fullDay'] ?? '';
                                $isWeekend = !empty($dayInfo['isWeekend']);
                                $eventsForDay = __getEventsForRoomAndDay($calendarLookup ?? [], $room->id, $fullDay);
                            @endphp

                            @if(empty($eventsForDay))
                                <td class="{{ $isWeekend ? 'weekend-bg-cell' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : 'background-color:#fff;' }} height: {{ $hDay }}px;">
                                    <div style="height:{{ $hMorning }}px;border-bottom:1px solid rgba(64,64,64,0.35);"></div>
                                    <div style="height:{{ $hNoon }}px;border-bottom:1px solid rgba(64,64,64,0.35);"></div>
                                </td>
                            @else
                                <td class="{{ $isWeekend ? 'weekend-bg-cell' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : 'background-color:#fff;' }} height: {{ $hDay }}px;"
                                >
                                    <div class="day-wrap" style="height: {{ $hDay }}px;">
                                        @php $renderDayCell($eventsForDay, $fullDay, $hMorning, $hNoon, $hEvening); @endphp
                                    </div>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
@endforeach

</body>
</html>
