<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: 7px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header { border-bottom: 1px solid #18181b; margin-bottom: 5px; padding-bottom: 4px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: 0; padding: 0; vertical-align: top; }
        .title { font-size: 13px; font-weight: 700; }
        .meta { color: #52525b; font-size: 7px; margin-top: 2px; }
        .week { font-size: 11px; font-weight: 700; text-align: right; }
        table.matrix { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .matrix th, .matrix td { border: 0.5px solid #a1a1aa; padding: 3px; vertical-align: top; }
        .matrix thead { display: table-header-group; }
        .matrix tr { page-break-inside: avoid; }
        .matrix th { background: #27272a; color: #fff; font-size: 7px; text-align: left; }
        .matrix th.worker, .matrix td.worker { width: 34mm; }
        .matrix th.day { text-align: center; }
        .matrix th.weekend { background: #3f3f46; }
        .worker-name { font-size: 8px; font-weight: 700; }
        .worker-meta { color: #71717a; font-size: 6px; margin-top: 1px; }
        .day-cell { min-height: 19px; }
        .day-cell.weekend { background: #fafafa; }
        .entry { border-left: 2px solid #52525b; margin-bottom: 2px; padding: 1px 2px; }
        .entry:last-child { margin-bottom: 0; }
        .entry-time { font-weight: 700; white-space: nowrap; }
        .entry-room { font-weight: 700; }
        .entry-meta { color: #52525b; font-size: 6px; }
        .entry-description { color: #71717a; font-size: 6px; font-style: italic; }
        .individual { background: #f4f4f5; border-left-color: #a1a1aa; }
        .service { border-left-width: 3px; }
        .status { float: right; font-size: 6px; }
        .empty { color: #a1a1aa; text-align: center; }
        .holiday { color: #fca5a5; display: block; font-size: 6px; font-weight: 400; margin-top: 1px; }
        .legend { color: #52525b; font-size: 6px; margin-top: 4px; }
    </style>
</head>
<body>
@foreach($pages as $page)
    <section class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <div class="title">{{ $title }}</div>
                        <div class="meta">
                            {{ __('Period') }}: {{ $startDate }} - {{ $endDate }}@if(!empty($kwRange)) ({{ $kwRange }})@endif ·
                            {{ __('Workers') }}: {{ $workerCount }} ·
                            {{ __('Created by') }}: {{ $createdBy }} · {{ $createdAt }}
                        </div>
                        @if(!empty($craftFilterNames))
                            <div class="meta">
                                {{ __('Crafts') }}: {{ implode(', ', $craftFilterNames) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="week">KW {{ $page['weekNumber'] }} / {{ $page['weekYear'] }}</div>
                        @if($page['workerPageCount'] > 1)
                            <div class="meta" style="text-align:right;">
                                {{ __('Personnel page') }} {{ $page['workerPage'] }}/{{ $page['workerPageCount'] }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <table class="matrix">
            <thead>
                <tr>
                    <th class="worker">{{ __('Worker') }}</th>
                    @foreach($page['days'] as $day)
                        <th class="day {{ $day['isWeekend'] ? 'weekend' : '' }}">
                            {{ $day['dayName'] }}<br>{{ $day['dateLabel'] }}
                            @if($day['holiday'])
                                <span class="holiday">{{ $day['holiday'] }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($page['workers'] as $worker)
                    <tr>
                        <td class="worker">
                            <div class="worker-name">{{ $worker['displayName'] }}</div>
                            <div class="worker-meta">{{ $worker['typeLabel'] }}</div>
                            @if($worker['craftLabel'])
                                <div class="worker-meta">{{ $worker['craftLabel'] }}</div>
                            @endif
                        </td>
                        @foreach($page['days'] as $day)
                            @php($cell = $worker['cells'][$day['date']] ?? ['shifts' => [], 'individualTimes' => [], 'dayServices' => []])
                            <td class="day-cell {{ $day['isWeekend'] ? 'weekend' : '' }}">
                                @foreach($cell['shifts'] as $shift)
                                    <div class="entry">
                                        <span class="status">{{ $shift['committed'] ? '✓' : ($shift['inWorkflow'] ? '…' : '') }}</span>
                                        <div class="entry-time">{{ $shift['start'] }}-{{ $shift['end'] }}</div>
                                        <div class="entry-room">{{ $shift['room'] }}</div>
                                        @if($shift['craft'] || $shift['function'])
                                            <div class="entry-meta">
                                                {{ collect([$shift['craft'], $shift['function']])->filter()->join(' · ') }}
                                            </div>
                                        @endif
                                        @if($shift['description'])
                                            <div class="entry-description">{{ $shift['description'] }}</div>
                                        @endif
                                    </div>
                                @endforeach

                                @foreach($cell['individualTimes'] as $individualTime)
                                    <div class="entry individual">
                                        <div class="entry-time">
                                            {{ $individualTime['fullDay'] ? __('All day') : $individualTime['start'] . '-' . $individualTime['end'] }}
                                        </div>
                                        <div>{{ $individualTime['title'] }}</div>
                                    </div>
                                @endforeach

                                @foreach($cell['dayServices'] as $dayService)
                                    <div class="entry service" style="border-left-color: {{ $dayService['color'] }};">
                                        <div class="entry-meta">{{ __('Day service') }}</div>
                                        <div class="entry-room">{{ $dayService['name'] }}</div>
                                    </div>
                                @endforeach

                                @if($cell['shifts'] === [] && $cell['individualTimes'] === [] && $cell['dayServices'] === [])
                                    <div class="empty">-</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($page['days']) + 1 }}" class="empty" style="padding:12px;">
                            {{ __('No workers with assignments in the selected period.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="legend">
            ✓ {{ __('Committed') }} · … {{ __('Requested') }} ·
            {{ __('Gray') }} = {{ __('Individual time') }} · {{ __('Colored bar') }} = {{ __('Day service') }}
        </div>
    </section>
@endforeach
</body>
</html>
