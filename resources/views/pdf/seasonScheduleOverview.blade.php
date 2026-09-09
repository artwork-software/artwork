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

        // KW-Spalte entfällt komplett, wenn die Kalenderwochen abgewählt sind
        $showWeekNumbers = (bool) ($showWeekNumbers ?? true);
        $columnsPerMonth = $showWeekNumbers ? 3 : 2;

        // Feste Zellhöhe, damit die Tageszeilen immer auf eine Seite passen —
        // eine wachsende Zelle würde sonst die ganze Tageszeile strecken und
        // die Tabelle auf ein zweites Blatt schieben. Werte bei dpi 72 kalibriert.
        $rowClipHeight = match(strtolower($paperSize ?? 'a3')) {
            'a4' => $splitMonths ? 52 : 26,
            default => $splitMonths ? 77 : 39,
        };

        // Nutzbare Tabellenbreite in px (Querformat, 8mm Seitenrand je Seite),
        // dpi-72-kalibriert wie die Zeilenhöhen. Basis für die Zeichen-Garantie
        // der Schriftgröße weiter unten.
        $pageWidthMm = strtolower($paperSize ?? 'a3') === 'a4' ? 297.0 : 420.0;
        $usableWidthPx = ($pageWidthMm - 16) * 4.3;
        // Mittlere Zeichenbreite einer halbfetten Grotesk in em
        $charWidthFactor = 0.58;
        // So viele Zeichen eines Namens müssen immer ungekürzt lesbar bleiben;
        // die Schrift wächst nie über diese Grenze hinaus.
        $minVisibleChars = max(6, (int) ($minVisibleChars ?? 16));

        // Adaptive Schriftgröße pro Zelle: wenige Einträge -> große Schrift,
        // viele Einträge -> kleiner bzw. zweispaltig (Grenzwerte unten in px, skaliert)
        $maxEntryFont = 12 * $scaleFactor;
        $twoColumnFontCap = 7.5 * $scaleFactor;
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

        /* Wochentag + Tageszahl in einer Spalte ("Mo 1") spart Breite für die Inhalte */
        td.day-label {
            text-align: center;
            font-size: {{ $s(7.5) }};
            font-weight: 700;
            white-space: nowrap;
        }
        td.day-label .weekday {
            font-weight: 400;
            color: #333;
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
        /* Farbe des Projekts als Hintergrund der Schrift statt als Punkt davor */
        .entry-name.colored {
            padding: 0 2px;
            border-radius: 2px;
        }

        .entry-more {
            font-size: {{ $s(5) }};
            font-weight: 700;
            color: #555;
            white-space: nowrap;
        }

        .entry-count { font-weight: 800; }
        .entry-rooms { font-weight: 400; color: #444; }
    </style>
</head>
<body>

@php
    /**
     * Lesbare Schriftfarbe auf farbigem Grund (relative Helligkeit nach WCAG-Näherung).
     */
    $readableTextColor = static function (?string $hex): string {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '#111';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#111' : '#fff';
    };
@endphp

@foreach($pages as $pageIndex => $pageMonths)
    @php
        $monthCount = count($pageMonths);
        // Spaltenbreiten je Monat (Summe = 100 / Monatsanzahl): Datum | Inhalt | (KW)
        $monthWidth = 100 / $monthCount;
        $dayWidth = $monthWidth * 0.15;
        $kwWidth = $showWeekNumbers ? $monthWidth * 0.09 : 0;
        $contentWidth = $monthWidth - $dayWidth - $kwWidth;
        // Effektiv beschreibbare Breite einer Inhaltszelle (abzüglich Zellinnenabstand)
        $contentInnerPx = max(20.0, $usableWidthPx * $contentWidth / 100 - 6);
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
                    <col style="width: {{ round($contentWidth, 3) }}%;">
                    @if($showWeekNumbers)
                        <col style="width: {{ round($kwWidth, 3) }}%;">
                    @endif
                @endforeach
            </colgroup>
            <thead>
                <tr>
                    @foreach($pageMonths as $month)
                        <th colspan="{{ $columnsPerMonth }}">{{ $month['label'] }}@if($splitMonths) · {{ $rangeFirstDay }}.–{{ $rangeLastDay }}.@endif</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($dayNumber = $rangeFirstDay; $dayNumber <= $rangeLastDay; $dayNumber++)
                    <tr>
                        @foreach($pageMonths as $month)
                            @php
                                $day = $month['days'][$dayNumber] ?? null;
                                $lastColumnClass = $showWeekNumbers ? '' : 'month-end';
                            @endphp
                            @if($day === null)
                                <td class="month-start void-bg"></td>
                                <td class="void-bg {{ $lastColumnClass }}"></td>
                                @if($showWeekNumbers)
                                    <td class="month-end void-bg"></td>
                                @endif
                            @elseif($day['outOfRange'] ?? false)
                                {{-- Tag liegt vor dem Start-/nach dem Enddatum: sichtbar, aber ausgegraut --}}
                                <td class="day-label month-start void-bg" style="color: #77777c;">
                                    <span class="weekday" style="color: #77777c;">{{ $day['weekday'] }}</span> {{ $day['dayNumber'] }}
                                </td>
                                <td class="void-bg {{ $lastColumnClass }}"></td>
                                @if($showWeekNumbers)
                                    <td class="month-end void-bg"></td>
                                @endif
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

                                    // Adaptive Schrift: so groß wie möglich, ohne dass die Zelle wächst.
                                    // Zwei Deckel greifen — die Höhe (alle Einträge müssen in die feste
                                    // Zellhöhe passen) und die Breite (die ersten $minVisibleChars Zeichen
                                    // eines Namens müssen ungekürzt stehen bleiben). Zweispaltig wird nur
                                    // gesetzt, wenn das unterm Strich die größere Schrift ergibt.
                                    $effectiveClip = $rowClipHeight - ($day['holidayName'] ? $holidayLineHeight : 0);
                                    $columns = 1;
                                    $entryFont = 0;
                                    $hiddenCount = 0;
                                    if ($entryCount > 0) {
                                        $longestName = 1;
                                        $longestSuffix = 0;
                                        foreach ($entries as $entry) {
                                            $longestName = max($longestName, mb_strlen((string) $entry['name']));
                                            $suffix = ($entry['count'] > 1 ? mb_strlen('(' . $entry['count'] . ')') + 1 : 0)
                                                + (!empty($entry['rooms']) ? mb_strlen(implode('/', $entry['rooms'])) + 2 : 0);
                                            $longestSuffix = max($longestSuffix, $suffix);
                                        }
                                        $charsToFit = min($longestName, $minVisibleChars) + $longestSuffix;
                                        $fontByWidth = static fn (int $cols): float =>
                                            $contentInnerPx * ($cols === 2 ? 0.49 : 1.0)
                                            / max(1.0, $charsToFit * $charWidthFactor);

                                        $singleColumnFont = min(
                                            $maxEntryFont,
                                            $effectiveClip / $entryCount / 1.25,
                                            $fontByWidth(1)
                                        );
                                        $twoColumnFont = $entryCount > 2
                                            ? min(
                                                $twoColumnFontCap,
                                                $effectiveClip / ceil($entryCount / 2) / 1.25,
                                                $fontByWidth(2)
                                            )
                                            : 0;

                                        if ($twoColumnFont > $singleColumnFont) {
                                            $columns = 2;
                                            $entryFont = $twoColumnFont;
                                        } else {
                                            $entryFont = $singleColumnFont;
                                        }

                                        // Notanker: unterhalb der Minimalschrift lieber "+x weitere"
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
                                <td class="day-label month-start {{ $bgClass }}">
                                    <span class="weekday">{{ $day['weekday'] }}</span> {{ $day['dayNumber'] }}
                                </td>
                                <td class="content {{ $bgClass }} {{ $lastColumnClass }}">
                                    <div class="cell-clip">
                                    @if($day['holidayName'])
                                        <div class="holiday-name">{{ $day['holidayName'] }}</div>
                                    @endif
                                    @foreach($entries as $entry)
                                        @php
                                            $hasSuffix = $entry['count'] > 1 || !empty($entry['rooms']);
                                            $entryColor = ($showEntryColors ?? false) && !empty($entry['color'])
                                                ? $entry['color']
                                                : null;
                                            $nameStyle = 'max-width: ' . ($hasSuffix ? '68%' : '100%') . ';';
                                            if ($entryColor !== null) {
                                                $nameStyle .= ' background-color: ' . $entryColor . ';'
                                                    . ' color: ' . $readableTextColor($entryColor) . ';';
                                            }
                                        @endphp
                                        <div class="entry-line {{ $columns === 2 ? 'col-2' : '' }}" style="font-size: {{ $entryFont }}px;"><span class="entry-name {{ $entryColor !== null ? 'colored' : '' }}" style="{{ $nameStyle }}">{{ $entry['name'] }}</span>@if($entry['count'] > 1)
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
                                @if($showWeekNumbers)
                                    <td class="week-number month-end {{ $bgClass }}">{{ $day['weekNumber'] ?? '' }}</td>
                                @endif
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
