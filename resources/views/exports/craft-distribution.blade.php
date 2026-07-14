<table>
    <thead>
    <tr>
        <th colspan="{{ $totalCols }}">{{ __('Craft distribution', [], $language) }} {{ $universalCraftName }} {{ $periodLabel }}</th>
    </tr>
    <tr>
        <th rowspan="2">{{ __('Person', [], $language) }}</th>
        @foreach($crafts as $craft)
            <th colspan="2">{{ $craft['name'] }}</th>
        @endforeach
        <th colspan="2">{{ __('Other crafts', [], $language) }}</th>
        <th rowspan="2">{{ __('Total', [], $language) }} ({{ __('Hours', [], $language) }})</th>
    </tr>
    <tr>
        @for($group = 0; $group <= count($crafts); $group++)
            <th>{{ __('Hours', [], $language) }}</th>
            <th>{{ __('Share', [], $language) }}</th>
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
            <td>{{ $row['total_hours'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td>{{ $totalRow['label'] }}</td>
        @foreach($totalRow['values'] as $value)
            <td>{{ $value }}</td>
        @endforeach
        <td>{{ $totalRow['total_hours'] }}</td>
    </tr>
    </tbody>
</table>
