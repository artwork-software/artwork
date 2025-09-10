<html lang="en">
<head>
    <title>Donation Log</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style>
        @import url(https://fonts.bunny.net/css?family=poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i);
        body {
            font-family: 'poppins', sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        table, th, td {
            border: 1px solid #cccccc;
        }

        th, td {
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        thead > tr > th {
            font-weight: bold;
            font-size: 8px;
        }

    </style>
</head>
<body>


<div style="padding: 15px 30px; background-color: #ccc;">
    <div style="font-weight: bold; font-size: 14px;">
        Per Diem List
    </div>
    <div style="font-size: 8px">
        {{ $project?->name }} - KST: {{ $project?->costCenter?->name }}
    </div>
    <div style="font-size: 8px">
        {{ __('export.date', [], $language) }}: {{ now()->format('d.m.Y H:i') }}, {{ __('export.by', [], $language) }}: {{ $user->full_name }}
    </div>
</div>

<div style="margin-top: 10px;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('export.artist_name', [], $language) }}</th>
                <th>{{ __('export.arrival_time', [], $language) }}</th>
                <th>{{ __('export.departure_time', [], $language) }}</th>
                <th>{{ __('export.nights_count', [], $language) }}</th>
                <th>{{ __('export.daily_allowance', [], $language) }}</th>
                <th>{{ __('export.additional_allowance', [], $language) }}</th>
                <th>{{ __('export.cost_per_night', [], $language) }}</th>
                <th>{{ __('export.total', [], $language) }}</th>
                <th>{{ __('export.get_sum', [], $language) }}</th>
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
                    <td>{{ number_format($artistResidency->daily_allowance, 2, ',', '.') }} €</td>
                    <td style="text-align: center">{{ $artistResidency->additional_daily_allowance}}</td>
                    <td>{{ number_format($artistResidency->cost_per_night, 2, ',', '.') }} €</td>
                    <td>{{ number_format($totalCost, 2, ',', '.') }} €</td>
                    <td></td>
                </tr>
            @endforeach

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
                <td colspan="4" style="text-align: right; font-weight: bold">{{ __('export.total_days', [], $language) }}:</td>
                <td style="text-align: center; text-decoration-line: underline">{{ $artistResidencies->sum('days') }}</td>
                <td colspan="3" style="text-align: right; font-weight: bold">{{ __('export.total_sum', [], $language) }}:</td>
                <td style="text-decoration-line: underline">{{ number_format($totalCost, 2, ',', '.') }} €</td>
                <td></td>
            </tr>
        </tbody>
    </table>


</div>



</body>
</html>
