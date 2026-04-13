<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @php
        $scaleFactor = match(strtolower($paperSize ?? 'a3')) {
            'a3' => 1.0,
            'a4' => 0.75,
            'a5' => 0.6,
            'a6' => 0.5,
            default => 1.0,
        };
        $s = fn(float $base) => round($base * $scaleFactor, 1) . 'px';
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

        /* HEADER – only first page */
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
            vertical-align: top;
        }

        .header-left {
            text-align: left;
            vertical-align: middle;
        }

        .header-title {
            font-size: {{ $s(18) }};
            font-weight: 700;
            color: #000000;
            display: inline;
        }

        .header-subtitle {
            font-size: {{ $s(11) }};
            color: #000000;
            display: inline;
            margin-left: 6px;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
        }

        .header-right img {
            max-height: 32px;
            max-width: 140px;
        }

        /* TABLE */
        table.monthly {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.5px solid #404040;
        }

        /* Make table fill remaining page height */
        .first-page table.monthly {
            height: calc(100% - 22px);
        }
        .subsequent-page table.monthly {
            height: 100%;
        }

        table.monthly th,
        table.monthly td {
            border: 1px solid #404040;
            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow: hidden;
        }

        table.monthly thead th {
            background: #f9fafb;
            font-weight: 600;
            text-align: center;
            padding: 2px 1px;
            border-bottom: 1.5px solid #404040;
            white-space: nowrap;
            font-size: {{ $s(6) }};
        }

        table.monthly thead th.corner {
            text-align: left;
            font-weight: 700;
            color: #0a0a0a;
            padding: 3px 4px;
        }

        table.monthly tbody td.day-label {
            font-weight: 700;
            font-size: {{ $s(12) }};
            white-space: nowrap;
            padding: 1px 3px;
            vertical-align: middle;
            width: 75px;
            min-width: 75px;
            max-width: 75px;
        }

        .weekend-bg { background-color: #f4f4f5; }
        .weekday-bg { background-color: #ffffff; }

        /* EVENT BUBBLE */
        .evt {
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,0.35);
            border-left-width: 3px;
            padding: 1px 2px;
            margin-bottom: 1px;
            overflow: hidden;
            line-height: 1.15;
        }

        .evt-line1 {
            font-size: {{ $s(10) }};
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000000 !important;
        }

        .evt-line2 {
            font-size: {{ $s(10) }};
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000000 !important;
        }

        .abbr {
            font-weight: 900;
        }
    </style>
</head>
<body>

@foreach($pages as $pageIndex => $page)
    <div class="page {{ $pageIndex === 0 ? 'first-page' : 'subsequent-page' }}">
        @if($pageIndex === 0)
            <div class="page-header">
                <table>
                    <tr>
                        <td class="header-left">
                            <span class="header-title">{{ $title }}</span>
                        </td>
                        <td class="header-center">
                            <span class="header-subtitle">Erstellt am {{ $created_date }} von {{ $created_by }}</span>
                        </td>
                        <td class="header-right">
                            @if($bigLogoBase64)
                                <img src="{{ $bigLogoBase64 }}" alt="Logo" />
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <table class="monthly">
            <thead>
                <tr>
                    <th class="corner" style="width: 75px; white-space: nowrap;">{{ $page['monthLabel'] ?? $title }}</th>
                    @foreach($rooms as $room)
                        <th>{{ $room->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($page['days'] as $day)
                    @php
                        $bgClass = $day['isWeekend'] ? 'weekend-bg' : 'weekday-bg';
                    @endphp
                    <tr>
                        <td class="day-label {{ $bgClass }}">{{ $day['display'] }}</td>
                        @foreach($rooms as $room)
                            @php
                                $events = $calendarLookup[$room->id][$day['fullDay']]['events'] ?? [];
                            @endphp
                            <td class="{{ $bgClass }}">
                                @foreach($events as $event)
                                    @php
                                        // Color based on colorSource setting
                                        if (($colorSource ?? 'eventType') === 'mainCategory') {
                                            if (!$event->project) {
                                                $hexColor = '#9E9E9E'; // grey for events without project
                                            } elseif ($event->mainCategoryColor) {
                                                $hexColor = $event->mainCategoryColor;
                                            } else {
                                                $hexColor = '#3A3A3A'; // anthracite for project without main category
                                            }
                                        } else {
                                            $hexColor = $event->eventType->hex_code ?? '#111111';
                                        }

                                        $abbr = $event->eventType->abbreviation ?? '';
                                        $eventTypeName = $event->eventType->name ?? '';
                                        $projectName = $event->project->name ?? null;
                                        $eventName = $event->eventName ?? null;
                                        $artistNames = $event->artistNames ?? null;

                                        $startFormatted = \Carbon\Carbon::parse($event->start)->format('H:i');
                                        $endFormatted = \Carbon\Carbon::parse($event->end)->format('H:i');

                                        $r = hexdec(substr($hexColor, 1, 2));
                                        $g = hexdec(substr($hexColor, 3, 2));
                                        $b = hexdec(substr($hexColor, 5, 2));

                                        $bgR = (int)round($r * 0.14 + 255 * 0.86);
                                        $bgG = (int)round($g * 0.14 + 255 * 0.86);
                                        $bgB = (int)round($b * 0.14 + 255 * 0.86);
                                        $bgRGB = "rgb($bgR,$bgG,$bgB)";
                                        $borderRGB = "rgba($r,$g,$b,0.95)";
                                        $leftRGB = "rgba($r,$g,$b,1)";

                                        $line1 = $startFormatted . '-' . $endFormatted . ' ' . $abbr . ' ' . $eventTypeName;

                                        $line2Parts = [];
                                        if ($projectName) $line2Parts[] = $projectName;
                                        if ($eventName) $line2Parts[] = $eventName;
                                        if ($artistNames) $line2Parts[] = $artistNames;
                                        $line2 = implode(', ', $line2Parts);
                                    @endphp
                                    <div class="evt" style="background:{{ $bgRGB }}; border-color:{{ $borderRGB }}; border-left-color:{{ $leftRGB }};">
                                        <div class="evt-line1">
                                            {{ $line1 }}
                                        </div>
                                        @if($line2)
                                            <div class="evt-line2">
                                                {{ $line2 }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach

</body>
</html>
