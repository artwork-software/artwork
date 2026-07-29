<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #18181b;
            margin: 0;
        }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .header {
            border-bottom: 2px solid #18181b;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .header .title { font-size: 15px; font-weight: 700; }
        .header .month { font-size: 12px; color: #3f3f46; margin-top: 2px; }
        .header .logo { max-height: 38px; max-width: 130px; }
        .summary { text-align: right; font-size: 10px; line-height: 1.4; white-space: nowrap; }
        .summary .ist { font-weight: 700; }
        .summary .sep { color: #d4d4d8; margin: 0 5px; }
        .summary .diff-pos { color: #15803d; font-weight: 700; }
        .summary .diff-neg { color: #b91c1c; font-weight: 700; }

        table.cal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.cal th {
            background: #18181b;
            color: #fff;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 4px;
            border: 1px solid #d4d4d8;
        }
        table.cal th.kw { width: 26px; background: #3f3f46; }
        table.cal th.we { background: #1e3a8a; }

        td.kw {
            border: 1px solid #d4d4d8;
            background: #fafafa;
            text-align: center;
            font-size: 8px;
            color: #71717a;
            vertical-align: middle;
        }
        td.day {
            border: 1px solid #d4d4d8;
            vertical-align: top;
            padding: 2px;
            width: 13.7%;
            height: 90px; /* Mindesthöhe – Zellen wachsen bei vollem Inhalt mit */
        }
        td.out { background: #f4f4f5; }
        td.weekend { background: #dbeafe; }
        td.weekend .daynum { color: #1e3a8a; }
        td.holiday { background: #fef2f2; }

        .daynum { font-size: 10px; font-weight: 700; color: #3f3f46; margin-bottom: 2px; }
        td.out .daynum { color: #a1a1aa; font-weight: 600; }
        .holiday-name { font-size: 7px; color: #b91c1c; margin-bottom: 2px; }

        .shift {
            border: 1px solid #e4e4e7;
            border-radius: 3px;
            margin-bottom: 2px;
            overflow: hidden;
        }
        .shift .stime {
            background: #3730a3;
            color: #fff;
            font-weight: 700;
            font-size: 8px;
            padding: 1px 3px;
        }
        .shift .body { padding: 2px 3px; }
        .shift .line { font-size: 7.5px; color: #3f3f46; line-height: 1.3; }
        .shift .line.project { font-weight: 700; color: #18181b; }
        .shift .line.desc { color: #52525b; }
        .shift .line.note { color: #6d28d9; font-style: italic; }
        .shift .line.colleagues { color: #71717a; }
        .shift .committed { color: #bbf7d0; font-weight: 700; }

        .it {
            border: 1px solid #e4e4e7;
            border-radius: 3px;
            margin-bottom: 2px;
            overflow: hidden;
        }
        .it .stime {
            background: #a1a1aa;
            color: #fff;
            font-weight: 700;
            font-size: 8px;
            padding: 1px 3px;
        }
        .it .body { padding: 2px 3px; }
        .it .ittitle { font-size: 7.5px; color: #52525b; }

        /* Verbindliche Projektzuordnung ohne konkrete Schicht */
        .pa {
            border: 1px solid #bae6fd;
            background: #f0f9ff;
            color: #075985;
            border-radius: 3px;
            margin-bottom: 2px;
            padding: 2px 3px;
            font-size: 7.5px;
            font-weight: 700;
        }

        .legend { margin-top: 8px; font-size: 7.5px; color: #71717a; }
        .legend span { margin-right: 14px; }
        .swatch {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
            vertical-align: middle;
            margin-right: 3px;
            border: 1px solid #d4d4d8;
        }
        .doc-footer {
            margin-top: 6px;
            font-size: 7px;
            color: #a1a1aa;
            text-align: right;
        }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <div class="page">
        <div class="header">
            <table>
                <tr>
                    <td>
                        <div class="title">{{ $userName }}</div>
                        <div class="month">{{ ucfirst($page['monthLabel']) }}</div>
                    </td>
                    <td style="text-align: right;">
                        @if ($bigLogoBase64)
                            <img class="logo" src="{{ $bigLogoBase64 }}" alt="">
                        @endif
                        <div class="summary">
                            <span class="ist">Ist {{ $page['monthName'] }}: {{ $page['totalWork'] }} h</span>
                            @if ($showSoll && $page['sollWork'])
                                <span class="sep">|</span>
                                <span>Soll {{ $page['monthName'] }}: {{ $page['sollWork'] }} h</span>
                                <span class="sep">|</span>
                                <span class="{{ $page['diffPositive'] ? 'diff-pos' : 'diff-neg' }}">Differenz: {{ $page['diffWork'] }} h</span>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="cal">
            <thead>
                <tr>
                    <th class="kw">KW</th>
                    @foreach ($page['weekDayNames'] as $dn)
                        <th class="{{ in_array($dn, ['Sa', 'So']) ? 'we' : '' }}">{{ $dn }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($page['weeks'] as $week)
                    <tr>
                        <td class="kw">{{ $week['kw'] }}</td>
                        @foreach ($week['cells'] as $cell)
                            <td class="day
                                {{ !$cell['inMonth'] ? 'out' : '' }}
                                {{ $cell['isHoliday'] ? 'holiday' : ($cell['isWeekend'] ? 'weekend' : '') }}">
                                <div class="daynum">{{ $cell['dayNumber'] }}</div>
                                @if ($cell['holidayName'])
                                    <div class="holiday-name">{{ $cell['holidayName'] }}</div>
                                @endif

                                @foreach ($cell['shifts'] as $shift)
                                    <div class="shift">
                                        <div class="stime" style="background-color: {{ $shift['color'] }};">
                                            {{ $shift['start'] }}–{{ $shift['end'] }}{{ $shift['craft'] ? ' ' . $shift['craft'] : '' }}{{ $shift['function'] ? ' ' . $shift['function'] : '' }}
                                            @if ($shift['committed'])<span class="committed">&#10003;</span>@endif
                                        </div>
                                        <div class="body">
                                            @if ($shift['room'])
                                                <div class="line">{{ $shift['room'] }}</div>
                                            @endif
                                            @if ($shift['project'])
                                                <div class="line project">{{ $shift['project'] }}</div>
                                            @endif
                                            @if ($shift['event'])
                                                <div class="line">{{ $shift['event'] }}</div>
                                            @endif
                                            @if ($shift['description'])
                                                <div class="line desc">{{ $shift['description'] }}</div>
                                            @endif
                                            @if ($shift['note'])
                                                <div class="line note">{{ $shift['note'] }}</div>
                                            @endif
                                            @if (count($shift['colleagues']))
                                                <div class="line colleagues">
                                                    mit: {{ implode(', ', $shift['colleagues']) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @foreach ($cell['individualTimes'] as $it)
                                    <div class="it">
                                        <div class="stime">
                                            @if ($it['full_day'] || (!$it['start'] && !$it['end']))
                                                Ganztägig
                                            @else
                                                {{ $it['start'] }}–{{ $it['end'] }}
                                            @endif
                                        </div>
                                        @if ($it['title'])
                                            <div class="body">
                                                <div class="ittitle">{{ $it['title'] }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Verbindliche Projektzuordnungen (ohne konkrete Schicht) --}}
                                @foreach (($cell['projectAssignments'] ?? []) as $assignmentProjectName)
                                    <div class="pa">{{ $assignmentProjectName }}</div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="legend">
            <span><span class="swatch" style="background:#dbeafe;"></span>Wochenende</span>
            <span><span class="swatch" style="background:#fef2f2;"></span>Feiertag</span>
            <span><span class="swatch" style="background:#f4f4f5;"></span>anderer Monat</span>
            <span><span class="swatch" style="background:#a1a1aa;"></span>Individuelle Zeit</span>
            <span><span class="swatch" style="background:#f0f9ff;"></span>Projektzuordnung</span>
            <span>&#10003; = bestätigt</span>
        </div>

        <div class="doc-footer">
            Erstellt von {{ $created_by }} am {{ $created_date }}
        </div>
    </div>
@endforeach
</body>
</html>
