<table>
    <thead>
    <tr>
        <th colspan="{{ $totalCols }}">{{ __('Project role overview', [], $language) }} {{ $periodLabel }}</th>
    </tr>
    <tr>
        <th rowspan="2">{{ __('Person', [], $language) }}</th>
        @forelse($projects as $project)
            <th>{{ $project['name'] }}</th>
        @empty
            <th>{{ __('No projects in the selected period.', [], $language) }}</th>
        @endforelse
    </tr>
    <tr>
        @forelse($projects as $project)
            <th>{{ $project['period_label'] }}</th>
        @empty
            <th></th>
        @endforelse
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['name'] }}</td>
            @foreach($projects as $project)
                <td>{{ $row['roles'][$project['id']] ?? '' }}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ $totalCols }}">{{ __('No persons with a fixed project role were assigned in the selected period.', [], $language) }}</td>
        </tr>
    @endforelse
    </tbody>
</table>
