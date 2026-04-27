<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kalender</title>
    @php
        $scaleFactor = match(strtolower($paperSize ?? 'a4')) {
            'a3' => 1.4,
            'a5' => 0.85,
            'a6' => 0.7,
            default => 1.0, // a4
        };
        $s = fn(float $base) => round($base * $scaleFactor, 1) . 'px';
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            font-size: {{ $s(8) }};
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
            font-size: {{ $s(8) }};
            line-height: 1.3;
            border: none;
            border-collapse: collapse;
        }

        .header-table td { vertical-align: top; padding: 0; border: none; }
        .header-left { width: 60%; font-size: {{ $s(8) }}; color: #111; }
        .header-right { width: 40%; text-align: right; font-size: {{ $s(7) }}; color: #71717a; line-height: 1.4; }

        .title { font-size: {{ $s(12) }}; font-weight: 600; color: #0a0a0a; line-height: 1.3; }
        .subtitle { font-size: {{ $s(8) }}; color: #52525b; margin-top: 2px; line-height: 1.4; }
        .chunk-info { font-size: {{ $s(7) }}; color: #71717a; line-height: 1.4; margin-top: 2px; }

        /* TABLE ------------------------------------------------------*/
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            border: 2px solid #404040;
            font-size: {{ $s(6) }};
            font-weight: 500;
        }

        th, td { word-wrap: break-word; }

        thead th {
            border-bottom: 2px solid #404040;
            border-right: 2px solid #404040;
            background: #f9fafb;
            vertical-align: top;
            font-size: {{ $s(6) }};
            font-weight: 500;
        }

        .th-room-head {
            font-weight: 500;
            font-size: {{ $s(6) }};
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
            font-size: {{ $s(6) }};
        }
        .th-daygroup-meta { font-weight: 500; font-size: {{ $s(6) }}; line-height: 1.2; color: #4b5563; }

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
            font-size: {{ $s(9) }};
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
            font-size: {{ $s(7) }};
        }

        .time-wrap { position: relative; width: 100%; overflow: hidden; }
        .time-block {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-size: {{ $s(7) }};
            font-weight: 700;
            color: #4b5563;
        }

        /* Day cell */
        .day-wrap { position: relative; width: 100%; background: #fff; overflow: hidden; }

        /* Lane Layout */
        .lanes {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .lane {
            float: left;
            height: 100%;
            position: relative;
            z-index: 0; /* eigener Stacking-Context pro Lane */
            padding: 2px;
            overflow: hidden;
            border-left: 1px solid rgba(64,64,64,0.4);
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
            font-size: {{ $s(11) }};
            line-height: 1.2;
            word-break: break-word;
            overflow: hidden;
        }
        .event-sub {
            margin-top: 1px;
            font-weight: 800;
            font-size: {{ $s(10) }};
            line-height: 1.2;
            color: #111;
            word-break: break-word;
            overflow: hidden;
        }
        .event-time {
            margin-top: 1px;
            font-weight: 800;
            font-size: {{ $s(8) }};
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
        }

        .event-compact .event-inner { padding: 1px 2px; }
        .event-compact .event-title { font-size: {{ $s(9.5) }}; line-height: 1.15; }
        .event-compact .event-sub   { display: none; }
        .event-compact .event-time  { font-size: {{ $s(7.5) }}; line-height: 1.1; }

        .event-supercompact .event-inner { padding: 1px 2px; }
        .event-supercompact .event-title { font-size: {{ $s(8.5) }}; line-height: 1.1; }
        .event-supercompact .event-sub   { display: none; }
        .event-supercompact .event-time  { font-size: {{ $s(7) }}; line-height: 1.05; }

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
     * Baut Segment für einen Tag (compact stacking mode):
     * Berechnet Zeitdaten + visuelle Eigenschaften, KEINE Pixelpositionierung.
     * Die Positionierung erfolgt kompakt in $renderDayCell.
     */
    function __buildSegmentForDay(
        $event,
        string $dayDisplay,
        int $hMorning, int $hNoon, int $hEvening,
        string $colorSource = 'eventType',
        int $laneCount = 1
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

        $startMin = max(6*60, min(24*60, $startMinRaw));
        $endMin   = max(6*60, min(24*60, $endMinRaw));

        // Stundenweise Quantisierung (für Lane-Assignment / Überlappungs-Erkennung)
        $qStart = __floorToHour($startMin);
        $qEnd   = __ceilToHour($endMin);
        if ($qEnd <= $qStart) $qEnd = min(24*60, $qStart + 60);

        // Mindesthöhe: basierend auf Textlänge und Lane-Breite
        $baseCharsPerLine = 14;
        $charsPerLine = max(4, (int) floor($baseCharsPerLine / max(1, $laneCount)));
        $_evName = $event->eventName ?? '';
        $_abbr   = $event->eventType?->abbreviation ?? '';
        $_projNm = $event->project->name ?? '';
        $_titleText = ($_abbr !== '' ? $_abbr . ': ' : '') . $_evName;
        $titleLines = max(1, (int) ceil(mb_strlen($_titleText) / $charsPerLine));
        $projectLines = $_projNm !== '' ? max(1, (int) ceil(mb_strlen($_projNm) / $charsPerLine)) : 0;
        // Zeilenhöhen passend zu den CSS font-sizes / line-heights
        $titleLineH   = 14; // .event-title: 11px * 1.2 + spacing
        $projectLineH = 14; // .event-sub:   10px * 1.2 + margin
        $timeLineH    = 12; // .event-time:   8px * 1.15 + margin
        $paddingPx    = 14; // .event-inner padding + borders + event border + extra breathing room
        $minHeightPx  = max(40,
            $titleLines * $titleLineH
            + $projectLines * $projectLineH
            + $timeLineH
            + $paddingPx
        );

        // Slot-Span (für Kompaktheitslogik)
        $slotSpan = 1;
        if ($qStart < 12*60 && $qEnd > 12*60) $slotSpan++;
        if ($qStart < 18*60 && $qEnd > 18*60) $slotSpan++;

        // Farben
        $abbr = $event->eventType?->abbreviation ?? '';
        if (($colorSource ?? 'eventType') === 'mainCategory') {
            if (!$event->project) {
                $hexColor = '#9E9E9E';
            } elseif ($event->mainCategoryColor ?? null) {
                $hexColor = $event->mainCategoryColor;
            } else {
                $hexColor = '#3A3A3A';
            }
        } else {
            $hexColor = $event->eventType?->hex_code ?? '#111111';
        }
        $name      = $event->eventName ?? '';
        $projectNm = $event->project->name ?? null;

        $r = hexdec(substr($hexColor, 1, 2));
        $g = hexdec(substr($hexColor, 3, 2));
        $b = hexdec(substr($hexColor, 5, 2));

        $mixWithWhite = function(int $r, int $g, int $b, float $t): string {
            $r2 = (int) round($r + (255 - $r) * $t);
            $g2 = (int) round($g + (255 - $g) * $t);
            $b2 = (int) round($b + (255 - $b) * $t);
            return sprintf('#%02X%02X%02X', $r2, $g2, $b2);
        };
        $bgHex      = $mixWithWhite($r, $g, $b, 0.85);
        $borderHex  = $mixWithWhite($r, $g, $b, 0.05);
        $leftHex    = sprintf('#%02X%02X%02X', $r, $g, $b);

        return [
            'qStart'      => $qStart,
            'qEnd'        => $qEnd,
            'allDay'      => $allDay,
            'minHeightPx' => $minHeightPx,
            'slotSpan'    => $slotSpan,
            'abbr'        => $abbr,
            'name'        => $name,
            'project'     => $projectNm,
            'time'        => $timeString,
            'isMulti'     => $isMultiDay,
            'bg'          => $bgHex,
            'border'      => $borderHex,
            'left'        => $leftHex,
            'rgb'         => [$r,$g,$b],
        ];
    }

    // Lane assignment (Zeitintervalle => keine Überlappung)
    function __assignLanes(array $segments): array {
        usort($segments, function($a, $b) {
            if ($a['qStart'] !== $b['qStart']) return $a['qStart'] <=> $b['qStart'];
            if ($a['qEnd'] !== $b['qEnd']) return $b['qEnd'] <=> $a['qEnd'];
            return 0;
        });

        $laneEnds = [];
        $byLane   = [];

        foreach ($segments as $seg) {
            $assigned = null;

            foreach ($laneEnds as $laneIndex => $endMin) {
                if ($endMin <= $seg['qStart']) {
                    $assigned = $laneIndex;
                    break;
                }
            }

            if ($assigned === null) {
                $assigned = count($laneEnds);
                $laneEnds[$assigned] = 0;
            }

            $laneEnds[$assigned] = $seg['qEnd'];
            $byLane[$assigned][] = $seg;
        }

        ksort($byLane);
        $laneCount = max(1, count($byLane));
        return [$byLane, $laneCount];
    }

    $renderDayCell = function(array $eventsForDay, string $dayDisplay, int $hMorning, int $hNoon, int $hEvening, string $colorSource = 'eventType') {
        // Pre-compute lane count so segment min-heights account for narrower lanes
        $preSegments = [];
        foreach ($eventsForDay as $event) {
            $seg = __buildSegmentForDay($event, $dayDisplay, $hMorning, $hNoon, $hEvening, $colorSource, 1);
            if ($seg) $preSegments[] = $seg;
        }
        [, $laneCount] = __assignLanes($preSegments);

        // Rebuild segments with correct lane count for accurate min-heights
        $segments = [];
        foreach ($eventsForDay as $event) {
            $seg = __buildSegmentForDay($event, $dayDisplay, $hMorning, $hNoon, $hEvening, $colorSource, $laneCount);
            if ($seg) $segments[] = $seg;
        }

        [$byLane, $laneCount] = __assignLanes($segments);

        $hDay = $hMorning + $hNoon + $hEvening;
        $GAP_PX = 4; // kleiner Abstand zwischen nicht-aufeinanderfolgenden Events

        // Slot-Grenzen in Pixeln
        $slotStartPxMap = [
            'morning' => 0,
            'noon'    => $hMorning,
            'evening' => $hMorning + $hNoon,
        ];

        // Compact Positioning: Events kompakt stapeln statt zeit-proportional
        foreach ($byLane as $laneIndex => &$laneSegments) {
            usort($laneSegments, fn($a, $b) => $a['qStart'] <=> $b['qStart']);

            $cursor = 0;
            $prevQEnd = null;

            foreach ($laneSegments as &$seg) {
                // Slot-Anfang des Events bestimmen
                $slot = __slotForMinute($seg['qStart']);
                $slotStart = $slotStartPxMap[$slot];

                // Cursor mindestens auf Slot-Anfang setzen
                if ($cursor < $slotStart) {
                    $cursor = $slotStart;
                }

                // Kleiner Abstand wenn Zeitlücke zum vorherigen Event
                if ($prevQEnd !== null && $seg['qStart'] > $prevQEnd) {
                    $cursor += $GAP_PX;
                }

                $seg['topPx'] = $cursor;
                $seg['heightPx'] = $seg['minHeightPx'];
                $seg['bottomPx'] = $cursor + $seg['minHeightPx'];

                $cursor = $seg['bottomPx'];
                $prevQEnd = $seg['qEnd'];
            }
            unset($seg);
        }
        unset($laneSegments);

        echo '<div class="lanes" style="height: '.$hDay.'px;">';

        $laneWidth = 100 / $laneCount;
        $segBorder = '1px solid rgba(64,64,64,0.45)';

        foreach ($byLane as $laneIndex => $laneSegments) {
            echo '<div class="lane" style="width: '.$laneWidth.'%; height: '.$hDay.'px;">';

            // Segment-Trennlinien innerhalb jeder Lane (z-index:1, unter Events z-index:10)
            echo '<div style="position:absolute;top:0;left:0;right:0;height:'.$hDay.'px;z-index:1;pointer-events:none;">';
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
                    echo '<span class="abbr" style="color: '.sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]).';">'.e($seg['abbr']).'</span>';
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
                    <th class="th-room-head" style="text-align:center; vertical-align:middle; font-size:{{ $s(9) }}; font-weight:700;">
                        Raum
                    </th>

                    <th class="th-room-head time-col-bg" style="padding: 1px; font-size: {{ $s(9) }}; font-weight: 700; line-height: 1.2; background-color:#f4f4f5; text-align:center; vertical-align:middle; white-space:nowrap;">
                        Zeit
                    </th>

                    @foreach($daysPage as $dayInfo)
                        @php $isWeekend = !empty($dayInfo['isWeekend']); @endphp
                        <th class="th-daygroup {{ $isWeekend ? 'weekend-bg-top' : '' }}"
                            style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }} font-size:{{ $s(9) }}; line-height:1.3; font-weight:700; padding:4px 2px; text-align:center;"
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
                                        @php $renderDayCell($eventsForDay, $fullDay, $hMorning, $hNoon, $hEvening, $colorSource ?? 'eventType'); @endphp
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
