<html lang="en">
<head>
    <title>
        {{ __('export.per_diem_list', [], $language) }}
    </title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style>
        @import url(https://fonts.bunny.net/css?family=poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i);
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            font-family: 'poppins', sans-serif;
            font-size: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
        }

        table, th, td {
            border: 1px solid #cccccc;
        }

        th, td {
            padding: 2px 3px;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background-color: #f2f2f2;
        }

        thead > tr > th {
            font-weight: bold;
            font-size: 6px;
        }

    </style>
</head>
<body>


<div style="padding: 8px 15px; background-color: #ccc;">
    <div style="font-weight: bold; font-size: 11px;">
        Per Diem List
    </div>
    <div style="font-size: 7px">
        {{ $project?->name }} - KST: {{ $project?->costCenter?->name }}
    </div>
    <div style="font-size: 7px">
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
                <th>{{ __('export.total_daily_allowance', [], $language) }}</th>
                <th>{{ __('export.breakfast_count', [], $language) }}</th>
                <th>{{ __('export.breakfast_deduction_total', [], $language) }}</th>
                <th>{{ __('export.payout_per_diem', [], $language) }}</th>
                <th>{{ __('export.cost_per_night', [], $language) }}</th>
                <th>{{ __('export.total', [], $language) }}</th>
                <th>{{ __('export.get_sum', [], $language) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($artistResidencies as $artistResidency)
                @php
                    $days = $artistResidency->days;
                    $dailyAllowanceTotal = $artistResidency->daily_allowance * ($days + $artistResidency->additional_daily_allowance);
                    $breakfastCount = $artistResidency->breakfast_count ?? 0;
                    $breakfastDeductionPerDay = $artistResidency->breakfast_deduction_per_day ?? 0;
                    $breakfastDeductionTotal = $breakfastCount * $breakfastDeductionPerDay;
                    $payoutPerDiem = $dailyAllowanceTotal - $breakfastDeductionTotal;
                    $overnightCost = $artistResidency->cost_per_night * $days;
                    $totalCost = $overnightCost + $payoutPerDiem;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $artistResidency?->artist?->name }}</td>
                    <td>{{ $artistResidency->formatted_dates['arrival_date'] }} {{ $artistResidency->formatted_dates['arrival_time'] }}</td>
                    <td>{{ $artistResidency->formatted_dates['departure_date'] }} {{ $artistResidency->formatted_dates['departure_time'] }}</td>
                    <td style="text-align: center">{{ $days }}</td>
                    <td>{{ number_format($artistResidency->daily_allowance, 2, ',', '.') }} €</td>
                    <td style="text-align: center">{{ $artistResidency->additional_daily_allowance }}</td>
                    <td>{{ number_format($dailyAllowanceTotal, 2, ',', '.') }} €</td>
                    <td style="text-align: center">{{ $breakfastCount }}</td>
                    <td>{{ number_format($breakfastDeductionTotal, 2, ',', '.') }} €</td>
                    <td>{{ number_format($payoutPerDiem, 2, ',', '.') }} €</td>
                    <td>{{ number_format($artistResidency->cost_per_night, 2, ',', '.') }} €</td>
                    <td>{{ number_format($totalCost, 2, ',', '.') }} €</td>
                    <td></td>
                </tr>
            @endforeach

            <tr>
                @php
                    $grandTotalDailyAllowance = 0;
                    $grandTotalBreakfastDeduction = 0;
                    $grandTotalPayoutPerDiem = 0;
                    $grandTotalOvernightCost = 0;
                    $grandTotal = 0;
                    foreach ($artistResidencies as $artistResidency) {
                        $days = $artistResidency->days;
                        $dailyAllowanceTotal = $artistResidency->daily_allowance * ($days + $artistResidency->additional_daily_allowance);
                        $breakfastCount = $artistResidency->breakfast_count ?? 0;
                        $breakfastDeductionPerDay = $artistResidency->breakfast_deduction_per_day ?? 0;
                        $breakfastDeductionTotal = $breakfastCount * $breakfastDeductionPerDay;
                        $payoutPerDiem = $dailyAllowanceTotal - $breakfastDeductionTotal;
                        $overnightCost = $artistResidency->cost_per_night * $days;
                        $grandTotalDailyAllowance += $dailyAllowanceTotal;
                        $grandTotalBreakfastDeduction += $breakfastDeductionTotal;
                        $grandTotalPayoutPerDiem += $payoutPerDiem;
                        $grandTotalOvernightCost += $overnightCost;
                        $grandTotal += $overnightCost + $payoutPerDiem;
                    }
                @endphp
                <td colspan="4" style="text-align: right; font-weight: bold">{{ __('export.total_days', [], $language) }}:</td>
                <td style="text-align: center; text-decoration-line: underline">{{ $artistResidencies->sum('days') }}</td>
                <td></td>
                <td></td>
                <td style="text-decoration-line: underline">{{ number_format($grandTotalDailyAllowance, 2, ',', '.') }} €</td>
                <td></td>
                <td style="text-decoration-line: underline">{{ number_format($grandTotalBreakfastDeduction, 2, ',', '.') }} €</td>
                <td style="text-decoration-line: underline">{{ number_format($grandTotalPayoutPerDiem, 2, ',', '.') }} €</td>
                <td></td>
                <td style="text-decoration-line: underline; font-weight: bold">{{ number_format($grandTotal, 2, ',', '.') }} €</td>
                <td></td>
            </tr>
        </tbody>
    </table>


</div>



</body>
</html>
