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

        .page {
            page-break-after: always;
            padding: 16px;
        }
        .page:last-child { page-break-after: auto; }

        /* HEADER ------------------------------------------------------*/
        .header-table {
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 6px;
            font-size: 8px;
            line-height: 1.3;
            border: none;
            border-collapse: collapse; /* keine sichtbaren Rahmen */
        }

        .header-table td {
            vertical-align: top; /* gleiche Höhe oben */
            padding: 0;
            border: none;
        }

        .header-left {
            width: 60%;
            font-size: 8px;
            color: #111;
        }

        .header-right {
            width: 40%;
            text-align: right;
            font-size: 7px;
            color: #71717a;
            line-height: 1.4;
        }

        .title {
            font-size: 12px;
            font-weight: 600;
            color: #0a0a0a;
            line-height: 1.3;
        }
        .subtitle {
            font-size: 8px;
            color: #52525b;
            margin-top: 2px;
            line-height: 1.4;
        }
        .chunk-info {
            font-size: 7px;
            color: #71717a;
            line-height: 1.4;
            margin-top: 2px;
        }

        /* TABLE ------------------------------------------------------*/
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;   /* <--- WICHTIG */
            border: 1px solid #404040;
            font-size: 6px;
            font-weight: 500;
        }

        /* Damit Inhalte nicht über die Zelle hinausquellen */
        th, td {
            word-wrap: break-word;
        }


        thead th {
            border-bottom: 1px solid #404040;
            border-right: 1px solid #404040;
            background: #f9fafb;
            vertical-align: top;
            word-wrap: break-word;
            font-size: 6px;
            font-weight: 500;
        }

        /* Kopfspalten */
        .th-room-head {
            font-weight: 500;
            font-size: 6px;
            line-height: 1.3;
            text-align: left;
            padding: 4px;
            color: #111827;
            border-right: 1px solid #404040;
        }

        .th-daygroup {
            text-align: center;
            color: #111827;
            border-right: 1px solid #404040;
            line-height: 1.3;
            padding: 4px 2px;
            font-weight: 500;
            font-size: 6px;
        }
        .th-daygroup-meta {
            font-weight: 500;
            font-size: 6px;
            line-height: 1.2;
            color: #4b5563;
        }

        /* Wochenendfärbung */
        .weekend-bg-top { background-color: #f4f4f5; }
        .weekend-bg-slot { background-color: #f4f4f5; }

        /* Zeitspalte leicht grau hinterlegen (ähnlich Wochenende) */
        .time-col-bg { background-color: #f4f4f5; }

        /* BODY ------------------------------------------------------*/
        tbody td {
            border-bottom: 1px solid #404040;
            border-right: 1px solid #404040;
            vertical-align: top;
            padding: 3px;
            word-wrap: break-word;
        }

        /* Raumspalte links */
        .td-room {
            padding: 2px;
            vertical-align: top;
        }

        .room-name-wrapper {
            line-height: 1.2;
            font-weight: 500;
            color: #000;
            font-size: 6px;
            word-break: break-word;
            max-height: 24px;
            overflow: hidden;
        }

        /* Slot/Zeitraum Spalte (jetzt schlanker) */
        .td-slot-label {
            font-size: 6px;
            line-height: 1.2;
            color: #4b5563;
            font-weight: 500;
            vertical-align: middle;
            text-align: center;
            padding: 1px;
            white-space: nowrap;
            /* gleiche Mindesthöhe wie Eventzellen */
            min-height: 32px;
        }

        .slot-cell {
            vertical-align: top;
            font-size: 6px;
            line-height: 1.25;
            padding: 3px;
            word-wrap: break-word;
            /* Mindesthöhe für leere Zeilen: Platz für einen 4-zeiligen Termin */
            min-height: 80px;
        }

        /* Innen-Wrapper in Slot-Zellen, damit DomPDF min-height zuverlässig anwendet */
        .slot-inner {
            min-height: 64px;
        }

        /* Event Bubble ----------------------------------------------*/
        .event {
            border-radius: 3px;
            border-width: 0.5px;
            border-style: solid;
            margin-bottom: 2px;
            padding: 3px 4px;
            font-size: 6px;
            line-height: 1.3;
            font-weight: 500;
            display: block;
        }
        .event-headerline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2px;
            font-weight: 500;
            font-size: 6px;
            line-height: 1.3;
        }
        .event-left { flex: 1 1 auto; min-width: 0; word-break: break-word; }
        .event-abbr { font-weight: 600; }
        .event-time {
            flex: 0 0 auto;
            font-weight: 500;
            font-size: 6px;
            line-height: 1.2;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

@php
    /**
     * Holt alle Events eines Raums an einem Tag.
     */
    function __getEventsForRoomAndDay($calendarLookup, $roomId, $dayDisplay) {
        return $calendarLookup[$roomId][$dayDisplay]['events'] ?? [];
    }

    /**
     * Rendert Events als Bubbles für einen Slot (morning|noon|evening).
     * Wenn ein Event über Zeitfenster geht, taucht es in mehreren Slots auf,
     * weil eventOverlapsSlot() für mehrere Slots true sein kann.
     */
    $renderEventsForSlot = function(array $events, string $dayDisplay, string $slot) {
        foreach ($events as $event) {
            if (!\App\Http\Controllers\ExportPDFController::eventOverlapsSlot($event, $dayDisplay, $slot)) {
                continue;
            }

            $abbr      = $event->eventType?->abbreviation ?? '';
            $hexColor  = $event->eventType?->hex_code ?? '#000000';
            $name      = $event->eventName ?? '';
            $projectNm = $event->project->name ?? null;
            $allDay    = $event->allDay ?? false;

            // Zeiten vorbereiten
            $startCarbon = \Illuminate\Support\Carbon::parse($event->start)->timezone(config('app.timezone'));
            $endCarbon   = \Illuminate\Support\Carbon::parse($event->end)->timezone(config('app.timezone'));

            // Geht über mehrere Kalendertage?
            $isMultiDay = $startCarbon->toDateString() !== $endCarbon->toDateString();

            if ($allDay) {
                $timeString = 'Ganztägig';
            } else {
                $timeString = $startCarbon->format('H:i') . '–' . $endCarbon->format('H:i');
            }

            // Farben aus Hex auf rgba runterbrechen
            $r = hexdec(substr($hexColor, 1, 2));
            $g = hexdec(substr($hexColor, 3, 2));
            $b = hexdec(substr($hexColor, 5, 2));
            $bgRGBA     = "rgba($r,$g,$b,0.15)";
            $borderRGBA = "rgba($r,$g,$b,0.3)";
            $textRGBA   = "rgba($r,$g,$b,1)";

            echo '<div class="event" style="background-color:'.$bgRGBA.';border-color:'.$borderRGBA.';color:'.$textRGBA.';">';
            echo    '<div class="event-headerline">';

            // Linker Block (Name, Projekt)
            echo        '<span class="event-left">';
            if ($abbr) {
                echo '<span class="event-abbr">'.$abbr.'</span>: ';
            }
            echo        e($name);
            if ($projectNm) {
                echo '<br>'.e($projectNm);
            }
            echo        '</span>';

            // Rechter Block (Zeit). Hier kommt das "!" bei mehrtägig
            echo        '<div class="event-time">';

            if ($isMultiDay) {
                echo '<span style="color:#dc2626;padding-right:2px;font-weight:600;">!</span>';

                $startHuman = $startCarbon->format('d.m. H:i');
                $endHuman   = $endCarbon->format('d.m. H:i');

                echo e($startHuman).' – '.e($endHuman);
            } else {
                echo e($timeString);
            }

            echo        '</div>'; // .event-time
            echo    '</div>'; // .event-headerline
            echo '</div>'; // .event
        }
    };


    // Für Zeitraum im Kopf
    $allDaysFlat = collect($dayChunks ?? [])->flatten(1);
    $firstDay    = $allDaysFlat->first()['fullDay'] ?? null;
    $lastDay     = $allDaysFlat->last()['fullDay'] ?? null;

@endphp

@foreach($roomChunks as $roomChunkIndex => $roomChunkPage)
    @foreach($dayChunks as $dayChunkIndex => $daysPage)
        @php
            $currentPageNumber = ($roomChunkIndex * count($dayChunks)) + $dayChunkIndex + 1;
            $totalPages        = count($roomChunks) * count($dayChunks);

            $firstRoomName = optional($roomChunkPage->first())->name ?? '';
            $lastRoomName  = optional($roomChunkPage->last())->name ?? '';

            $firstDayOnPage = $daysPage[0]['fullDay'] ?? '';
            $lastDayOnPage  = $daysPage[count($daysPage)-1]['fullDay'] ?? '';
        @endphp

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
                                    {{-- Linke Kopfspalte: Raum --}}
                    <th class="th-room-head" style="text-align:center; vertical-align:middle; font-size:9px; font-weight:700;">
                        Raum
                    </th>
                    {{-- Slot/Zeitraum Kopf (jetzt sehr klein) --}}
                    <th class="th-room-head time-col-bg" style="padding: 1px; font-size: 9px; font-weight: 700; line-height: 1.2; background-color:#f4f4f5; text-align:center; vertical-align:middle; white-space:nowrap;">
                        Zeit
                    </th>

                    {{-- Dann pro Tag EINE Spalte --}}
                    @php
                        // Berechne die Breite für jede Tagesspalte, damit alle gleich breit sind
                        $dayCount = count($daysPage);
                    @endphp
                    @foreach($daysPage as $dayInfo)
                        @php
                            $isWeekend = !empty($dayInfo['isWeekend']);
                        @endphp
                        <th class="th-daygroup {{ $isWeekend ? 'weekend-bg-top' : '' }}"
                            style="
                                {{ $isWeekend ? 'background-color:#f4f4f5;' : '' }}
                                font-size:9px;
                                line-height:1.3;
                                font-weight:700;
                                padding:4px 2px;
                                text-align:center;
                            "
                        >
                            <div>{{ $dayInfo['dayString'] }} {{ $dayInfo['fullDay'] }}</div>
                            <div class="th-daygroup-meta">
                                KW {{ $dayInfo['weekNumber'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
                </thead>

                <tbody>
                @foreach($roomChunkPage as $room)
                    @php
                        $roomName = $room->name;
                    @endphp

                    {{-- Zeile 1: Morgens --}}
                    @php $hMorning = $rowHeights[$room->id]['morning'] ?? 20; @endphp
                    <tr>
                        {{-- Leerer Platzhalter für Morgens-Zeile --}}
                        <td class="td-room"
                            style="
                                text-align:center;
                                vertical-align:top;
                                border-right:1px solid #404040;
                                border-bottom:none;
                            "
                        >
                            &nbsp;
                        </td>

                        {{-- Slot-Label Morgens --}}
                        <td class="td-slot-label time-col-bg" style="background-color:#f4f4f5; min-height: {{ $hMorning }}px;">
                            Morgens
                        </td>

                        {{-- Für jeden Tag die Events Morgens --}}
                        @foreach($daysPage as $dayInfo)
                            @php
                                $fullDay   = $dayInfo['fullDay'] ?? '';
                                $isWeekend = !empty($dayInfo['isWeekend']);
                                $eventsForDay = __getEventsForRoomAndDay($calendarLookup ?? [], $room->id, $fullDay);
                            @endphp

                            @if(empty($eventsForDay))
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }}">
                                </td>
                            @else
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }}"
                                >
                                    <div class="slot-inner" style="min-height: {{ $hMorning }}px;">
                                        @php $renderEventsForSlot($eventsForDay, $fullDay, 'morning'); @endphp
                                    </div>
                                </td>
                            @endif
                        @endforeach
                    </tr>

                    {{-- Zeile 2: Nachmittag --}}
                    @php $hNoon = $rowHeights[$room->id]['noon'] ?? 20; @endphp
                    <tr>
                        {{-- Raumzelle für Nachmittag-Zeile mit Raumnamen (größer und dicker) --}}
                        <td class="td-room"
                            style="
                                font-size:9px;
                                font-weight:700;
                                line-height:1.2;
                                word-break:break-word;
                                text-align:center;
                                vertical-align:middle;
                                border-right:1px solid #404040;
                                border-bottom:none;
                            "
                        >
                            {{ $roomName }}
                        </td>

                        <td class="td-slot-label time-col-bg" style="background-color:#f4f4f5; min-height: {{ $hNoon }}px;">
                            Mittags
                        </td>

                        @foreach($daysPage as $dayInfo)
                            @php
                                $fullDay   = $dayInfo['fullDay'] ?? '';
                                $isWeekend = !empty($dayInfo['isWeekend']);
                                $eventsForDay = __getEventsForRoomAndDay($calendarLookup ?? [], $room->id, $fullDay);
                            @endphp

                            @if(empty($eventsForDay))
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }}">
                                </td>
                            @else
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }}"
                                >
                                    <div class="slot-inner" style="min-height: {{ $hNoon }}px;">
                                        @php $renderEventsForSlot($eventsForDay, $fullDay, 'noon'); @endphp
                                    </div>
                                </td>
                            @endif
                        @endforeach
                    </tr>

                    {{-- Zeile 3: Abends --}}
                    @php $hEvening = $rowHeights[$room->id]['evening'] ?? 20; @endphp
                    <tr>
                        {{-- Leerer Platzhalter für Abends-Zeile --}}
                        <td class="td-room"
                            style="
                                text-align:center;
                                vertical-align:top;
                                border-right:1px solid #404040;
                                border-bottom:1px solid #404040;
                            "
                        >
                            &nbsp;
                        </td>

                        <td class="td-slot-label time-col-bg" style="border-bottom:1px solid #404040; background-color:#f4f4f5; min-height: {{ $hEvening }}px;">
                            Abends
                        </td>

                        @foreach($daysPage as $dayInfo)
                            @php
                                $fullDay   = $dayInfo['fullDay'] ?? '';
                                $isWeekend = !empty($dayInfo['isWeekend']);
                                $eventsForDay = __getEventsForRoomAndDay($calendarLookup ?? [], $room->id, $fullDay);
                            @endphp

                            @if(empty($eventsForDay))
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }} border-bottom:1px solid #404040;">
                                </td>
                            @else
                                <td class="slot-cell {{ $isWeekend ? 'weekend-bg-slot' : '' }}"
                                    style="{{ $isWeekend ? 'background-color:#f4f4f5;' : '' }} border-bottom:1px solid #404040;"
                                >
                                    <div class="slot-inner" style="min-height: {{ $hEvening }}px;">
                                        @php $renderEventsForSlot($eventsForDay, $fullDay, 'evening'); @endphp
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
