<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @php
        /**
         * Helper: convert a #rrggbb hex color into an "r, g, b" string so we can build rgba() tints.
         * Falls back to a neutral gray when the value is missing or malformed.
         */
        $rgb = function (?string $hex, string $fallback = '120, 120, 120'): string {
            $hex = ltrim((string) $hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
                return $fallback;
            }
            return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
        };

        // Trim "HH:MM:SS" / "Y-m-d H:i" to a short "HH:MM" representation.
        $time = function (?string $value): string {
            if (empty($value)) {
                return '';
            }
            if (preg_match('/(\d{2}):(\d{2})/', $value, $m)) {
                return $m[1] . ':' . $m[2];
            }
            return $value;
        };
    @endphp
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
            padding: 14px 16px;
        }
        .page:last-child { page-break-after: auto; }

        /* HEADER ---------------------------------------------------- */
        .header {
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 1.5px solid #111;
            padding-bottom: 5px;
        }
        .header .title {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
        }
        .header .kw {
            font-size: 12px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .header .meta {
            margin-top: 2px;
            font-size: 7.5px;
            color: #444;
            line-height: 1.4;
        }
        .header .meta .label { color: #888; }

        /* GRID ------------------------------------------------------ */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.grid th, table.grid td {
            border: 0.5px solid #d4d4d8;
            vertical-align: top;
        }
        table.grid thead th {
            background: #18181b;
            color: #fff;
            font-size: 8px;
            font-weight: 600;
            padding: 3px 4px;
            text-align: left;
        }
        table.grid thead th.weekend { background: #3f3f46; }
        table.grid thead th .date { font-weight: 400; opacity: 0.85; }

        th.room-col, td.room-col {
            width: 90px;
            background: #f4f4f5;
            font-weight: 600;
            font-size: 8px;
            padding: 4px;
            color: #18181b;
        }
        td.day-cell {
            padding: 2px;
            background: #fff;
        }
        td.day-cell.weekend { background: #fafafa; }

        /* EVENTS (single compact line — period overview, no intra-day detail) */
        .event {
            border-left: 2.5px solid #999;
            background: rgba(120,120,120,0.07);
            border-radius: 2px;
            padding: 1.5px 3px;
            margin-bottom: 2px;
            font-size: 7px;
            line-height: 1.25;
        }
        .event .ev-time { color: #555; font-weight: 600; font-size: 6.5px; }
        .event .ev-name { font-weight: 600; color: #18181b; }

        /* SHIFTS (head line + workers as flowing text) ---------------- */
        .shift {
            border-left: 3px solid #999;
            border-radius: 2px;
            padding: 2px 3px;
            margin-bottom: 2px;
        }
        .shift .sh-head { display: block; }
        .shift .sh-craft { font-size: 7.5px; font-weight: 700; color: #18181b; }
        .shift .sh-time { font-size: 6.5px; color: #555; }
        .shift .sh-badge {
            float: right;
            font-size: 6.5px;
            font-weight: 700;
            padding: 0 3px;
            border-radius: 6px;
            background: #e4e4e7;
            color: #3f3f46;
        }
        .shift .sh-badge.full { background: #dcfce7; color: #15803d; }
        .shift .sh-badge.open { background: #fee2e2; color: #b91c1c; }
        .shift .sh-workers {
            font-size: 7px;
            line-height: 1.3;
            color: #27272a;
        }
        .shift .sh-workers .open-slots { color: #b91c1c; font-style: italic; }
        .shift .sh-desc { font-size: 6.5px; color: #71717a; font-style: italic; }
        .committed-dot {
            display: inline-block;
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #16a34a;
            margin-left: 2px;
        }

        .empty-cell { color: #d4d4d8; font-size: 7px; text-align: center; padding-top: 6px; }

        /* PROJECT MODE HIGHLIGHTING ---------------------------------- */
        .proj-highlight {
            border: 1.5px solid #ec4899 !important;
            box-shadow: 0 0 0 1px #ec4899;
        }
        .proj-dimmed { opacity: 0.35; }
        .legend {
            display: inline-block;
            margin-top: 2px;
            font-size: 7px;
            color: #be185d;
            font-weight: 600;
        }
        .legend .swatch {
            display: inline-block;
            width: 8px; height: 8px;
            border: 1.5px solid #ec4899;
            border-radius: 2px;
            vertical-align: middle;
            margin-right: 3px;
        }
    </style>
</head>
<body>
@foreach($weeks as $week)
    <div class="page">
        <div class="header">
            <table style="width:100%; border:none; border-collapse:collapse;">
                <tr>
                    <td style="border:none; vertical-align:top;">
                        <div class="title">{{ $title }}</div>
                        <div class="meta">
                            <span class="label">{{ __('Period') }}:</span> {{ $startDate }} – {{ $endDate }}
                            @if(!empty($kwRange))
                                ({{ $kwRange }})
                            @endif
                            @if($project)
                                &nbsp;·&nbsp; <span class="label">{{ __('Project') }}:</span> {{ $project->name }}
                            @endif
                        </div>
                        <div class="meta">
                            @if(!empty($activeFilter['rooms']))
                                <span class="label">{{ __('Rooms') }}:</span> {{ implode(', ', $activeFilter['rooms']) }}&nbsp;&nbsp;
                            @endif
                            @if(!empty($activeFilter['areas']))
                                <span class="label">{{ __('Areas') }}:</span> {{ implode(', ', $activeFilter['areas']) }}&nbsp;&nbsp;
                            @endif
                            @if(!empty($activeFilter['event_types']))
                                <span class="label">{{ __('Event types') }}:</span> {{ implode(', ', $activeFilter['event_types']) }}&nbsp;&nbsp;
                            @endif
                            @if(!empty($activeFilter['crafts']))
                                <span class="label">{{ __('Crafts') }}:</span> {{ implode(', ', $activeFilter['crafts']) }}
                            @endif
                            @if(!empty($hideEmptyRooms))
                                <span class="label">{{ __('Hide empty rooms') }}</span>
                            @endif
                        </div>
                        @if($highlightProjectId)
                            <span class="legend">
                                <span class="swatch"></span>{{ __('Project mode') }}: {{ $highlightProjectName ?: ('#' . $highlightProjectId) }}
                            </span>
                        @endif
                    </td>
                    <td style="border:none; text-align:right; vertical-align:top; white-space:nowrap;">
                        <div class="kw">KW {{ $week['weekNumber'] }} · {{ $week['weekYear'] }}</div>
                        <div class="meta">
                            {{ __('Created by') }}: {{ $created_by }}<br>
                            {{ \Illuminate\Support\Carbon::now()->format('d.m.Y H:i') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <th class="room-col">{{ __('Room') }}</th>
                    @foreach($week['days'] as $day)
                        <th class="{{ $day['isWeekend'] ? 'weekend' : '' }}">
                            {{ $day['longDay'] }}<br>
                            <span class="date">{{ $day['fullDay'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @if(count($rooms) === 0)
                <tr>
                    <td class="empty-cell" colspan="{{ count($week['days']) + 1 }}" style="padding: 10px;">
                        {{ __('No rooms with events or shifts in the selected period.') }}
                    </td>
                </tr>
            @endif
            @foreach($rooms as $room)
                @php $roomBlock = $roomLookup[$room->id] ?? null; @endphp
                <tr>
                    <td class="room-col">{{ $room->name }}</td>
                    @foreach($week['days'] as $day)
                        @php
                            $cell = $roomBlock['content'][$day['fullDay']] ?? null;
                            $eventIds = $cell['eventIds'] ?? [];
                            $shiftIds = $cell['shiftIds'] ?? [];
                            $eventsById = $roomBlock['eventsById'] ?? [];
                            $shiftsById = $roomBlock['shiftsById'] ?? [];
                        @endphp
                        <td class="day-cell {{ $day['isWeekend'] ? 'weekend' : '' }}">
                            {{-- Events as context --}}
                            @foreach($eventIds as $eid)
                                @php
                                    $event = $eventsById[$eid] ?? null;
                                    if (!$event) { continue; }
                                    $et = $eventTypesById[$event['eventTypeId']] ?? null;
                                    $etRgb = $rgb($et['hex_code'] ?? null);
                                    $evProjClass = '';
                                    if ($highlightProjectId) {
                                        $evProjClass = (($event['projectId'] ?? null) == $highlightProjectId) ? ' proj-highlight' : ' proj-dimmed';
                                    }
                                @endphp
                                <div class="event{{ $evProjClass }}" style="border-left-color: rgb({{ $etRgb }}); background: rgba({{ $etRgb }}, 0.08);">
                                    <span class="ev-time">
                                        @if($event['allDay'] ?? false)
                                            {{ __('All day') }}
                                        @else
                                            {{ $time($event['start'] ?? null) }}–{{ $time($event['end'] ?? null) }}
                                        @endif
                                    </span>
                                    <span class="ev-name">{{ ($event['eventName'] ?? '') ?: ($et['name'] ?? '–') }}</span>
                                </div>
                            @endforeach

                            {{-- Shifts with full worker names --}}
                            @foreach($shiftIds as $sid)
                                @php
                                    $shift = $shiftsById[$sid] ?? null;
                                    if (!$shift) { continue; }
                                    $craft = $shift['craft'] ?? null;
                                    $craftRgb = $rgb($craft['color'] ?? null);
                                    $workers = $shift['workers'] ?? [];
                                    $assigned = count($workers);
                                    $needed = 0;
                                    foreach (($shift['shifts_qualifications'] ?? []) as $sq) {
                                        $needed += (int) ($sq['value'] ?? 0);
                                    }
                                    $open = max(0, $needed - $assigned);
                                    $badgeClass = $needed > 0 ? ($open === 0 ? 'full' : 'open') : '';
                                    $shProjClass = '';
                                    if ($highlightProjectId) {
                                        $shProjClass = (($shift['projectId'] ?? null) == $highlightProjectId) ? ' proj-highlight' : ' proj-dimmed';
                                    }
                                @endphp
                                <div class="shift{{ $shProjClass }}" style="border-left-color: rgb({{ $craftRgb }}); background: rgba({{ $craftRgb }}, 0.06);">
                                    <div class="sh-head">
                                        @if($needed > 0)
                                            <span class="sh-badge {{ $badgeClass }}">{{ $assigned }}/{{ $needed }}</span>
                                        @endif
                                        <span class="sh-craft">{{ $craft['abbreviation'] ?? ($craft['name'] ?? __('Shift')) }}</span>
                                        @if($shift['isCommitted'] ?? false)<span class="committed-dot"></span>@endif
                                        <span class="sh-time">
                                            {{ $time($shift['start'] ?? null) }}–{{ $time($shift['end'] ?? null) }}
                                            @if(($shift['break_minutes'] ?? 0) > 0)
                                                · {{ $shift['break_minutes'] }}′
                                            @endif
                                        </span>
                                    </div>
                                    @if(!empty($shift['description']))
                                        <div class="sh-desc">{{ $shift['description'] }}</div>
                                    @endif
                                    @php
                                        $workerNames = [];
                                        foreach ($workers as $worker) {
                                            $workerNames[] = ($worker['name'] ?? '') ?: '—';
                                        }
                                        $workersLine = implode(', ', $workerNames);
                                    @endphp
                                    @if($workersLine !== '' || $open > 0)
                                        <div class="sh-workers">{{ $workersLine }}{{ $workersLine !== '' && $open > 0 ? ', ' : '' }}@if($open > 0)<span class="open-slots">{{ $open }}× {{ __('Open') }}</span>@endif</div>
                                    @endif
                                </div>
                            @endforeach

                            @if(empty($eventIds) && empty($shiftIds))
                                <div class="empty-cell">–</div>
                            @endif
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
