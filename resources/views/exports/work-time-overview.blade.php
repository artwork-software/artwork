<table>
    <thead>
    <tr>
        <th colspan="{{ $totalCols }}">{{ __('Work time overview', [], $language) }} {{ $periodLabel }}</th>
    </tr>
    <tr>
        <th rowspan="2">{{ __('Month', [], $language) }}</th>
        @foreach($crafts as $craft)
            <th colspan="4">{{ $craft['name'] }}</th>
        @endforeach
        <th colspan="4">{{ __('Total', [], $language) }}</th>
    </tr>
    <tr>
        @for($group = 0; $group <= count($crafts); $group++)
            <th>{{ __('Target (internal)', [], $language) }}</th>
            <th>{{ __('Actual (internal)', [], $language) }}</th>
            <th>{{ __('Target (external)', [], $language) }}</th>
            <th>{{ __('Actual (external)', [], $language) }}</th>
        @endfor
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            @foreach($row['values'] as $value)
                <td>{{ $value }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
