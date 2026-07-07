<table>
    <tr>
        <td style="font-weight:bold;">
            {{ __('export.shift_plan.title', ['project' => $project->name], $language) }}
        </td>
        <td style="font-weight:bold;">
            {{ __('export.shift_plan.period', [], $language) }}
        </td>
        <td colspan="{{ max(1, $totalCols - 2) }}">
            {{ $periodLabel }}
        </td>
    </tr>

    <tr>
        <td colspan="7"></td>
        <td colspan="{{ (2 * $quals->count()) + 3 }}" style="text-align:center;">
            {{ __('export.shift_plan.sections.shifts', [], $language) }}
        </td>
        <td colspan="{{ (2 * $quals->count()) + 3 }}" style="text-align:center;">
            {{ __('export.shift_plan.sections.work_hours', [], $language) }}
        </td>
        <td></td>
    </tr>

    <tr>
        <td colspan="7"></td>

        <td colspan="{{ $quals->count() + 1 }}" style="text-align:center;">
            {{ __('export.shift_plan.subsections.internal', [], $language) }}
        </td>
        <td colspan="{{ $quals->count() + 1 }}" style="text-align:center;">
            {{ __('export.shift_plan.subsections.external', [], $language) }}
        </td>
        <td style="text-align:center;">
            {{ __('export.shift_plan.subsections.total', [], $language) }}
        </td>

        <td colspan="{{ $quals->count() + 1 }}" style="text-align:center;">
            {{ __('export.shift_plan.subsections.internal', [], $language) }}
        </td>
        <td colspan="{{ $quals->count() + 1 }}" style="text-align:center;">
            {{ __('export.shift_plan.subsections.external', [], $language) }}
        </td>
        <td style="text-align:center;">
            {{ __('export.shift_plan.subsections.total', [], $language) }}
        </td>

        <td></td>
    </tr>

    <tr>
        <td>{{ __('export.shift_plan.columns.craft', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.date', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.room', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.start', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.end', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.duration', [], $language) }}</td>
        <td>{{ __('export.shift_plan.columns.break_time', [], $language) }}</td>

        @foreach($quals as $q)
            <td>{{ $q->name }}</td>
        @endforeach
        <td>{{ __('export.shift_plan.symbols.sum', [], $language) }}</td>

        @foreach($quals as $q)
            <td>{{ $q->name }}</td>
        @endforeach
        <td>{{ __('export.shift_plan.symbols.sum', [], $language) }}</td>

        <td></td>

        @foreach($quals as $q)
            <td>{{ $q->name }}</td>
        @endforeach
        <td>{{ __('export.shift_plan.symbols.sum', [], $language) }}</td>

        @foreach($quals as $q)
            <td>{{ $q->name }}</td>
        @endforeach
        <td>{{ __('export.shift_plan.symbols.sum', [], $language) }}</td>

        <td></td>
        <td></td>
    </tr>

    <tr>
        <td colspan="{{ $totalCols }}"></td>
    </tr>

    @foreach($rows as $r)
        <tr>
            <td>{{ $r['craft'] }}</td>
            <td>{{ $r['date'] }}</td>
            <td>{{ $r['room'] }}</td>
            <td>{{ $r['start'] }}</td>
            <td>{{ $r['end'] }}</td>
            <td>{{ $r['duration_label'] }}</td>
            <td>{{ $r['break_label'] }}</td>

            @foreach($quals as $q)
                <td>{{ $r['int_counts'][$q->id] ?? 0 }}</td>
            @endforeach
            <td>{{ $r['int_sum'] }}</td>

            @foreach($quals as $q)
                <td>{{ $r['ext_counts'][$q->id] ?? 0 }}</td>
            @endforeach
            <td>{{ $r['ext_sum'] }}</td>

            <td>{{ $r['total_headcount'] }}</td>

            @foreach($quals as $q)
                <td>{{ $r['int_labels'][$q->id] ?? __('export.shift_plan.defaults.zero_duration', [], $language) }}</td>
            @endforeach
            <td>{{ $r['int_sum_label'] }}</td>

            @foreach($quals as $q)
                <td>{{ $r['ext_labels'][$q->id] ?? __('export.shift_plan.defaults.zero_duration', [], $language) }}</td>
            @endforeach
            <td>{{ $r['ext_sum_label'] }}</td>

            <td>{{ $r['total_label'] }}</td>
            <td></td>
        </tr>
    @endforeach

    <tr>
        <td colspan="{{ 7 + (2*$quals->count()+2) }}"></td>
        <td>{{ $totalHeadcount }}</td>
        <td colspan="{{ (2*$quals->count()+2) }}"></td>
        <td>{{ $totalMinutesLabel }}</td>
        <td></td>
    </tr>
</table>
