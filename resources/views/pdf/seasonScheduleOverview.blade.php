<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @php
        $scaleFactor = match(strtolower($paperSize ?? 'a3')) {
            'a3' => 1.0,
            'a4' => 0.72,
            default => 1.0,
        };
        $s = fn(float $base) => round($base * $scaleFactor, 1) . 'px';

        // Halbmonats-Modus: nur 16 statt 31 Tageszeilen pro Seite -> doppelte Zeilenhöhe
        $splitMonths = (bool) ($splitMonths ?? false);
        $dayRanges = $splitMonths ? [[1, 16], [17, 31]] : [[1, 31]];

        // Feste Zellhöhe, damit die Tageszeilen immer auf eine Seite passen —
        // eine wachsende Zelle würde sonst die ganze Tageszeile strecken und
        // die Tabelle auf ein zweites Blatt schieben. Werte bei dpi 72 kalibriert.
        $rowClipHeight = match(strtolower($paperSize ?? 'a3')) {
            'a4' => $splitMonths ? 52 : 26,
            default => $splitMonths ? 77 : 39,
        };

        // Adaptive Schriftgröße pro Zelle: wenige Einträge -> große Schrift,
        // viele Einträge -> kleiner bzw. zweispaltig (Grenzwerte unten in px, skaliert)
        $maxEntryFont = 10 * $scaleFactor;
        $twoColumnFontCap = 7.5 * $scaleFactor;
        $singleColumnMinFont = 6 * $scaleFactor;
        $minEntryFont = 5 * $scaleFactor;
        $holidayLineHeight = 7 * $scaleFactor;
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            margin: 5mm 8mm;
        }

        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
            font-size: {{ $s(7) }};
            color: #111;
            -webkit-font-smoothing: antialiased;
        }

        .page {
            page-break-after: always;
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        .page:last-child { page-break-after: auto; }

        /* HEADER – nur erste Seite */
        .page-header {
            width: 100%;
            margin-bottom: 2px;
        }
        .page-header table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .page-header table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .header-title {
            font-size: {{ $s(16) }};
            font-weight: 700;
            color: #000;
        }
        .header-subtitle {
            font-size: {{ $s(9) }};
            color: #000;
            margin-left: 6px;
        }
        .header-legend {
            font-size: {{ $s(8) }};
            color: #333;
        }
        .header-center { text-align: center; }
        .header-right { text-align: right; }
        .header-right img {
            max-height: 28px;
            max-width: 130px;
        }

        /* RASTER */
        table.season {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.5px solid #404040;
        }
        .first-page table.season { height: calc(100% - 24px); }
        .subsequent-page table.season { height: 100%; }

        table.season th,
        table.season td {
            border: 1px solid #9a9a9a;
            padding: 0 1px;
            vertical-align: middle;
            overflow: hidden;
        }

        table.season thead th {
            background: #f9fafb;
            font-weight: 700;
            text-align: center;
            padding: 2px 1px;
            border: 1px solid #404040;
            border-bottom: 1.5px solid #404040;
            white-space: nowrap;
            font-size: {{ $s(9) }};
        }

        /* Monatsgrenzen kräftiger als die inneren Sub-Spalten */
        td.month-start { border-left: 1.5px solid #404040; }
        td.month-end { border-right: 1.5px solid #404040; }

        td.day-number {
            text-align: center;
            font-weight: 700;
            font-size: {{ $s(7) }};
            white-space: nowrap;
        }
        td.weekday {
            text-align: center;
            font-size: {{ $s(6.5) }};
            white-space: nowrap;
        }
        td.week-number {
            text-align: center;
            font-size: {{ $s(5.5) }};
            color: #555;
            white-space: nowrap;
        }
        td.content {
            vertical-align: middle;
            line-height: 1.1;
        }
        .cell-clip {
            height: {{ $rowClipHeight }}px;
            overflow: hidden;
        }

        .saturday-bg { background-color: #f1f1f2; }
        .sunday-bg { background-color: #e2e2e5; }
        .holiday-bg { background-color: #fdf3d7; }
        .void-bg { background-color: #cfcfd4; }

        .holiday-name {
            font-style: italic;
            font-size: {{ $s(5.5) }};
            color: #6b5b17;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Adaptive Verdichtung: Schriftgröße kommt pro Zelle inline (siehe unten),
           nie stillschweigend abschneiden, sondern kompakter werden */
        .entry-line {
            white-space: nowrap;
            overflow: hidden;
            font-weight: 600;
            line-height: 1.2;
        }
        .entry-line.col-2 {
            display: inline-block;
            width: 49%;
            vertical-align: top;
        }
        /* Name kürzt per Ellipsis, damit "(n)" und Raumkürzel dahinter sichtbar bleiben */
        .entry-name {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }

        .entry-more {
            font-size: {{ $s(5) }};
            font-weight: 700;
            color: #555;
            white-space: nowrap;
        }

        .dot {
            display: inline-block;
            width: {{ $s(4) }};
            height: {{ $s(4) }};
            border-radius: 50%;
            margin-right: 1px;
            vertical-align: baseline;
        }
        .entry-count { font-weight: 800; }
        .entry-rooms { font-weight: 400; color: #444; }
    </style>
</head>
<body>

@foreach($pages as $pageIndex => $pageMonths)
    @php
        $monthCount = count($pageMonths);
        // Spaltenbreiten je Monat (Summe = 100 / Monatsanzahl): Tag | Wochentag | Inhalt | KW
        $monthWidth = 100 / $monthCount;
        $dayWidth = $monthWidth * 0.13;
        $weekdayWidth = $monthWidth * 0.13;
        $kwWidth = $monthWidth * 0.09;
        $contentWidth = $monthWidth - $dayWidth - $weekdayWidth - $kwWidth;
    @endphp
    @foreach($dayRanges as $rangeIndex => $dayRange)
    @php [$rangeFirstDay, $rangeLastDay] = $dayRange; @endphp
    <div class="page {{ ($pageIndex === 0 && $rangeIndex === 0) ? 'first-page' : 'subsequent-page' }}">
        @if($pageIndex === 0 && $rangeIndex === 0)
            <div class="page-header">
                <table>
                    <tr>
                        <td>
                            <span class="header-title">{{ $title }}</span>
                            <span class="header-subtitle">{{ $periodLabel }}</span>
                        </td>
                        <td class="header-center">
                            @if(!empty($eventTypeFilterNames))
                                <span class="header-legend">Terminarten: {{ implode(', ', $eventTypeFilterNames) }}</span>
                            @endif
                        </td>
                        <td class="header-right">
                            <span class="header-subtitle">Erstellt am {{ $created_date }} von {{ $created_by }}</span>
                            @if($bigLogoBase64)
                                <img src="{{ $bigLogoBase64 }}" alt="Logo" style="margin-left: 8px;" />
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <table class="season">
            <colgroup>
                @foreach($pageMonths as $month)
                    <col style="width: {{ round($dayWidth, 3) }}%;">
                    <col style="width: {{ round($weekdayWidth, 3) }}%;">
                    <col style="width: {{ round($contentWidth, 3) }}%;">
                    <col style="width: {{ round($kwWidth, 3) }}%;">
                @endforeach
            </colgroup>
            <thead>
                <tr>
                    @foreach($pageMonths as $month)
                        <th colspan="4">{{ $month['label'] }}@if($splitMonths) · {{ $rangeFirstDay }}.–{{ $rangeLastDay }}.@endif</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($dayNumber = $rangeFirstDay; $dayNumber <= $rangeLastDay; $dayNumber++)
                    <tr>
                        @foreach($pageMonths as $month)
                            @php
                                $day = $month['days'][$dayNumber] ?? null;
                            @endphp
                            @if($day === null)
                                <td class="month-start void-bg"></td>
                                <td class="void-bg"></td>
                                <td class="void-bg"></td>
                                <td class="month-end void-bg"></td>
                            @elseif($day['outOfRange'] ?? false)
                                {{-- Tag liegt vor dem Start-/nach dem Enddatum: sichtbar, aber ausgegraut --}}
                                <td class="day-number month-start void-bg" style="color: #77777c;">{{ $day['dayNumber'] }}</td>
                                <td class="weekday void-bg" style="color: #77777c;">{{ $day['weekday'] }}</td>
                                <td class="void-bg"></td>
                                <td class="month-end void-bg"></td>
                            @else
                                @php
                                    if ($day['isHoliday']) {
                                        $bgClass = 'holiday-bg';
                                    } elseif ($day['isSunday']) {
                                        $bgClass = 'sunday-bg';
                                    } elseif ($day['isSaturday']) {
                                        $bgClass = 'saturday-bg';
                                    } else {
                                        $bgClass = '';
                                    }

                                    $entries = $day['entries'];
                                    $entryCount = count($entries);

                                    // Adaptive Schrift: je weniger Einträge, desto größer.
                                    // Erst einspaltig so groß wie möglich; wird die Schrift zu
                                    // klein, auf zwei Spalten wechseln; unterhalb der Minimal-
                                    // schrift greift der Notanker "+x weitere".
                                    $effectiveClip = $rowClipHeight - ($day['holidayName'] ? $holidayLineHeight : 0);
                                    $columns = 1;
                                    $entryFont = 0;
                                    $hiddenCount = 0;
                                    if ($entryCount > 0) {
                                        $entryFont = min($maxEntryFont, $effectiveClip / $entryCount / 1.25);
                                        if ($entryFont < $singleColumnMinFont && $entryCount > 2) {
                                            $columns = 2;
                                            $entryFont = min($twoColumnFontCap, $effectiveClip / ceil($entryCount / 2) / 1.25);
                                        }
                                        if ($entryFont < $minEntryFont) {
                                            $entryFont = $minEntryFont;
                                            $linesAvailable = max(1, (int) floor($effectiveClip / ($entryFont * 1.25)));
                                            $capacity = $linesAvailable * $columns;
                                            if ($entryCount > $capacity) {
                                                $visibleCount = max(1, $capacity - 1);
                                                $hiddenCount = $entryCount - $visibleCount;
                                                $entries = array_slice($entries, 0, $visibleCount);
                                            }
                                        }
                                        $entryFont = round($entryFont, 1);
                                    }
                                @endphp
                                <td class="day-number month-start {{ $bgClass }}">{{ $day['dayNumber'] }}</td>
                                <td class="weekday {{ $bgClass }}">{{ $day['weekday'] }}</td>
                                <td class="content {{ $bgClass }}">
                                    <div class="cell-clip">
                                    @if($day['holidayName'])
                                        <div class="holiday-name">{{ $day['holidayName'] }}</div>
                                    @endif
                                    @foreach($entries as $entry)
                                        @php
                                            $hasSuffix = $entry['count'] > 1 || !empty($entry['rooms']);
                                        @endphp
                                        <div class="entry-line {{ $columns === 2 ? 'col-2' : '' }}" style="font-size: {{ $entryFont }}px;">
                                            @if(($showColorDots ?? false) && !empty($entry['color']))
                                                <span class="dot" style="background: {{ $entry['color'] }};"></span>
                                            @endif<span class="entry-name" style="max-width: {{ $hasSuffix ? '68%' : '100%' }};">{{ $entry['name'] }}</span>@if($entry['count'] > 1)
                                                <span class="entry-count">({{ $entry['count'] }})</span>
                                            @endif
                                            @if(!empty($entry['rooms']))
                                                <span class="entry-rooms">· {{ implode('/', $entry['rooms']) }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($hiddenCount > 0)
                                        <div class="entry-more">+{{ $hiddenCount }} weitere</div>
                                    @endif
                                    </div>
                                </td>
                                <td class="week-number month-end {{ $bgClass }}">{{ $day['weekNumber'] ?? '' }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    @endforeach
@endforeach

</body>
</html>
