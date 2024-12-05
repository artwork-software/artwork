<table>
    <tbody>
        <tr>
            <td></td>
            <td style="font-size: 12px;">
                Per Diem List
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                {{ $project?->name }} - KST: {{ $project?->costCenter?->name }}
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
        </tr>
    </thead>
    <tbody>
    @foreach($artistResidencies as $artistResidency)
        @php
            $totalCostWithout = ($artistResidency->cost_per_night * $artistResidency->days);
            $totalCostWith =  ($artistResidency->daily_allowance * $artistResidency->days) + $artistResidency->additional_daily_allowance;
            $totalCost = $totalCostWithout + $totalCostWith;
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $artistResidency->name }}</td>
            <td>{{ $artistResidency->formatted_dates['arrival_date'] }} {{ $artistResidency->formatted_dates['arrival_time'] }}</td>
            <td>{{ $artistResidency->formatted_dates['departure_date'] }} {{ $artistResidency->formatted_dates['departure_time'] }}</td>
            <td>{{ $artistResidency->days }}</td>
            <td>{{ number_format($artistResidency->daily_allowance, 2, ',', '.') }} €</td>
            <td>{{ number_format($artistResidency->additional_daily_allowance, 2, ',', '.') }} €</td>
            <td>{{ number_format($artistResidency->cost_per_night, 2, ',', '.') }} €</td>
            <td>{{ number_format($totalCost, 2, ',', '.') }} €</td>
        </tr>
    @endforeach

    <tr>
        @php
            // brechne die Gesamtkosten für die Tage ohne und mit zusätzlicher Tagespauschale

            $totalCostWithout = 0;
            $totalCostWith = 0;
            $totalCost = 0;
            foreach ($artistResidencies as $artistResidency) {
                $totalCostWithout += ($artistResidency->cost_per_night * $artistResidency->days);
                $totalCostWith += ($artistResidency->daily_allowance * $artistResidency->days) + $artistResidency->additional_daily_allowance;
            }
            $totalCost = $totalCostWithout + $totalCostWith;
        @endphp
        <td colspan="4" style="text-align: right; font-weight: bold">Total Days:</td>
        <td style="text-align: left; text-decoration-line: underline">{{ $artistResidencies->sum('days') }}</td>
        <td colspan="3" style="text-align: right; font-weight: bold">Total</td>
        <td style="text-decoration-line: underline">{{ number_format($totalCost, 2, ',', '.') }} €</td>
    </tr>
    </tbody>
</table>