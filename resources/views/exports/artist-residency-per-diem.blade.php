<table>
    <tbody>
        <tr>
            <td style="background-color: #cccccc; font-weight: bold;"></td>
            <td style="font-size: 12px; background-color: #cccccc; font-weight: bold;">
                Per Diem List
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                {{ $project?->name }} - KST: {{ $project?->costCenter?->name ?? $project?->cost_center?->name }}
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                {{ __('export.date', [], $language) }}: {{ now()->format('d.m.Y H:i') }}, {{ __('export.by', [], $language) }}: {{ $user->full_name }}
            </td>
        </tr>
    </tbody>
</table>


<table>
    <thead>
        <tr>
            <th style="background-color: #cccccc; font-weight: bold;">#</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.artist_name', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.arrival_time', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.departure_time', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.nights_count', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.daily_allowance', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.additional_allowance', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.cost_per_night', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.total', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.get_sum', [], $language) }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($artistResidencies as $artistResidency)
        @php
            $totalCostWithout = ($artistResidency->cost_per_night * $artistResidency->days);
            $totalCostWith =  ($artistResidency->daily_allowance * $artistResidency->additional_daily_allowance) + $totalCostWithout;
            $totalCost = $totalCostWith;
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $artistResidency?->artist?->name }}</td>
            <td>{{ $artistResidency->formatted_dates['arrival_date'] }} {{ $artistResidency->formatted_dates['arrival_time'] }}</td>
            <td>{{ $artistResidency->formatted_dates['departure_date'] }} {{ $artistResidency->formatted_dates['departure_time'] }}</td>
            <td style="text-align: center">{{ $artistResidency->days }}</td>
            <td data-format="0.00">{{ $artistResidency->daily_allowance }}</td>
            <td style="text-align: center">{{ $artistResidency->additional_daily_allowance }}</td>
            <td data-format="0.00">{{ $artistResidency->cost_per_night }}</td>
            <td data-format="0.00">{{ $totalCost }}</td>
            <td></td>
        </tr>
    @endforeach
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        @php
            // brechne die Gesamtkosten für die Tage ohne und mit zusätzlicher Tagespauschale
            $totalCostWith = 0;
            $totalCost = 0;
            foreach ($artistResidencies as $artistResidency) {
                $totalCostWithout = ($artistResidency->cost_per_night * $artistResidency->days);
                $totalCostWith += ($artistResidency->daily_allowance * $artistResidency->additional_daily_allowance) + $totalCostWithout;
            }
            $totalCost = $totalCostWith;
        @endphp
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: right; font-weight: bold; background-color: #cccccc">{{ __('export.total_days', [], $language) }}:</td>
        <td style="text-align: center; text-decoration-line: underline; background-color: #cccccc">{{ $artistResidencies->sum('days') }}</td>
        <td></td>
        <td></td>
        <td style="text-align: right; font-weight: bold; background-color: #cccccc">{{ __('export.total_sum', [], $language) }}:</td>
        <td style="text-decoration-line: underline; background-color: #cccccc" data-format="0.00">{{ $totalCost }}</td>
        <td></td>
    </tr>
    </tbody>
</table>
