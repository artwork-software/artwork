<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        /* Optik wie pdf.user_shift_plan (Monats-PDF je Person) */
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
        .header .sub { font-size: 12px; color: #3f3f46; margin-top: 2px; }
        .header .logo { max-height: 38px; max-width: 130px; }
        .header .stand { text-align: right; font-size: 9px; color: #3f3f46; line-height: 1.4; }

        .section-title { font-size: 11px; font-weight: 700; margin: 10px 0 4px; }
        .section-title .hint { font-weight: 400; color: #b45309; }

        table.cal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.cal th {
            background: #18181b;
            color: #fff;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 4px;
            border: 1px solid #d4d4d8;
        }
        table.cal th.we { background: #1e3a8a; }
        table.cal th .date { font-weight: 400; opacity: 0.85; }

        td.day {
            border: 1px solid #d4d4d8;
            vertical-align: top;
            padding: 2px;
            width: 14.28%;
            height: 150px; /* Mindesthöhe, Zellen wachsen bei vollem Inhalt mit */
        }
        td.weekend { background: #dbeafe; }
        td.holiday { background: #fef2f2; }
        .holiday-name { font-size: 7px; color: #b91c1c; margin-bottom: 2px; }
        .empty { font-size: 7.5px; color: #a1a1aa; }

        /* Vorschau: sichtbar zurückgenommen */
        table.preview td.day { height: 70px; }
        table.preview th { background: #71717a; }
        table.preview th.we { background: #64748b; }
        table.preview .shift .stime { opacity: 0.75; }

        .shift {
            border: 1px solid #e4e4e7;
            border-radius: 3px;
            margin-bottom: 2px;
            overflow: hidden;
            background: #fff;
        }
        .shift .stime {
            background: #3730a3;
            color: #fff;
            font-weight: 700;
            font-size: 8px;
            padding: 1px 3px;
        }
        .shift.open { border-style: dashed; border-color: #a1a1aa; }
        .shift .body { padding: 2px 3px; }
        .shift .line { font-size: 7.5px; color: #3f3f46; line-height: 1.3; }
        .shift .line.project { font-weight: 700; color: #18181b; }
        .shift .line.desc { color: #52525b; }
        .shift .line.note { color: #6d28d9; font-style: italic; }
        .shift .line.state { color: #15803d; font-weight: 700; }
        .shift.open .line.state { color: #b45309; }

        .it {
            border: 1px solid #e4e4e7;
            border-radius: 3px;
            margin-bottom: 2px;
            overflow: hidden;
            background: #fff;
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

        .tag {
            border-radius: 3px;
            margin-bottom: 2px;
            padding: 2px 3px;
            font-size: 7.5px;
            font-weight: 700;
        }
        .tag.free { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
        .tag.absent { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; }
        .tag.service { border: 1px solid #bae6fd; background: #f0f9ff; color: #075985; }

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
@forelse ($pages as $page)
    <div class="page">
        <div class="header">
            <table>
                <tr>
                    <td>
                        <div class="title">{{ $title }}: {{ $page['name'] }}</div>
                        <div class="sub">{{ $page['weekLabel'] }}</div>
                    </td>
                    <td style="text-align: right;">
                        @if ($bigLogoBase64)
                            <img class="logo" src="{{ $bigLogoBase64 }}" alt="">
                        @endif
                        <div class="stand">{{ __('As of') }}: {{ $createdAt }}</div>
                    </td>
                </tr>
            </table>
        </div>

        @foreach ([['days' => $page['days'], 'preview' => false], ['days' => $page['previewDays'], 'preview' => true]] as $block)
            @if ($block['preview'])
                <div class="section-title">
                    {{ __('Preview of the following week') }} ({{ $page['previewLabel'] }})
                    <span class="hint">· {{ __('non-binding, subject to change') }}</span>
                </div>
            @endif
            <table class="cal {{ $block['preview'] ? 'preview' : '' }}">
                <thead>
                    <tr>
                        @foreach ($block['days'] as $day)
                            <th class="{{ $day['isWeekend'] ? 'we' : '' }}">
                                {{ $day['weekday'] }} <span class="date">{{ $day['label'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach ($block['days'] as $day)
                            <td class="day {{ $day['holidays'] ? 'holiday' : ($day['isWeekend'] ? 'weekend' : '') }}">
                                @foreach ($day['holidays'] as $holidayName)
                                    <div class="holiday-name">{{ $holidayName }}</div>
                                @endforeach

                                @foreach ($day['freeDays'] as $freeDay)
                                    <div class="tag free">
                                        @if (!$freeDay['half'])
                                            {{ __('Day off') }}
                                        @elseif ($freeDay['period'] === 'morning')
                                            {{ __('Half day off (morning)') }}
                                        @elseif ($freeDay['period'] === 'afternoon')
                                            {{ __('Half day off (afternoon)') }}
                                        @else
                                            {{ __('Half day off') }}
                                        @endif
                                    </div>
                                @endforeach

                                @foreach ($day['absences'] as $absence)
                                    <div class="tag absent">
                                        {{ $absence['unavailable'] ? __('Not available') : __('Absent') }}@if (!$absence['full_day']) {{ $absence['start'] }}–{{ $absence['end'] }}@endif
                                    </div>
                                @endforeach

                                @foreach ($day['shifts'] as $shift)
                                    <div class="shift {{ $shift['committed'] ? '' : 'open' }}">
                                        <div class="stime" style="background-color: {{ $shift['craftColor'] }};">
                                            {{ $shift['start'] }}–{{ $shift['end'] }}@if ($shift['endsNextDay']) (+1)@endif{{ $shift['craft'] ? ' ' . $shift['craft'] : '' }}
                                        </div>
                                        <div class="body">
                                            @if ($shift['function'])
                                                <div class="line">{{ $shift['function'] }}</div>
                                            @endif
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
                                            @if (!$block['preview'])
                                                <div class="line state">
                                                    {{ $shift['committed'] ? __('binding') : __('not yet binding') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @foreach ($day['individualTimes'] as $it)
                                    <div class="it">
                                        <div class="stime">
                                            @if ($it['full_day'] || (!$it['start'] && !$it['end']))
                                                {{ __('All day') }}
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

                                @foreach ($day['dayServices'] as $dayServiceName)
                                    <div class="tag service">{{ $dayServiceName }}</div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endforeach

        <div class="legend">
            <span><span class="swatch" style="background:#f0fdf4;"></span>{{ __('Day off') }}</span>
            <span><span class="swatch" style="background:#fffbeb;"></span>{{ __('Absent') }}</span>
            <span><span class="swatch" style="background:#a1a1aa;"></span>{{ __('Individual time') }}</span>
            <span><span class="swatch" style="background:#f0f9ff;"></span>{{ __('Day service') }}</span>
            <span><span class="swatch" style="background:#fef2f2;"></span>{{ __('Public holiday') }}</span>
            <span>(+1) = {{ __('ends on the following day') }}</span>
        </div>

        <div class="doc-footer">
            {{ __('Created by :name on :date', ['name' => $createdBy, 'date' => $createdAt]) }}
        </div>
    </div>
@empty
    <div class="page">
        <div class="header">
            <div class="title">{{ $title }}</div>
        </div>
        <p>{{ __('No people with entries in the selected week.') }}</p>
    </div>
@endforelse
</body>
</html>
