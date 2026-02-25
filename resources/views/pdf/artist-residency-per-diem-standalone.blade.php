<html lang="en">
<head>
    <title>Per Diem Export</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @import url(https://fonts.bunny.net/css?family=poppins:300,400,500,600,700);
        @page {
            size: landscape;
            margin: 12mm;
        }
        body {
            font-family: 'poppins', sans-serif;
            font-size: 8px;
            color: #1a1a1a;
        }
        .header {
            width: 100%;
            margin-bottom: 15px;
        }
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .header-left p {
            margin: 0;
            padding: 0;
            line-height: 1.5;
            font-size: 9px;
        }
        .header-right p {
            margin: 0;
            padding: 0;
            line-height: 1.5;
            font-size: 9px;
        }
        .header-right img {
            max-height: 60px;
            max-width: 180px;
            margin-bottom: 5px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .date-line {
            margin-top: 20px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .details-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }
        table, th, td {
            border: 1px solid #cccccc;
        }
        th, td {
            padding: 3px 4px;
            text-align: left;
            white-space: nowrap;
        }
        th {
            background-color: #f2f2f2;
            font-weight: 600;
            font-size: 7px;
        }
        .confirmation {
            margin-top: 25px;
        }
        .confirmation h4 {
            font-size: 9px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .confirmation p {
            font-size: 8px;
            color: #444;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="header clearfix">
    <div class="header-left">
        <p style="font-weight: 600;">{{ __('export.per_diem_number', [], $language) }}</p>
        <p>{{ __('export.production', [], $language) }}: {{ $project?->name }}</p>
        <p>{{ __('export.cost_bearer', [], $language) }}: {{ $project?->costCenter?->name ?? '' }}</p>
    </div>
    <div class="header-right">
        @if($bigLogoBase64)
            <img src="{{ $bigLogoBase64 }}" alt="Logo" /><br>
        @endif
        @if($letterhead['name'])
            <p>{{ $letterhead['name'] }}</p>
        @endif
        @if($letterhead['street'] || $letterhead['zip_code'] || $letterhead['city'])
            <p>{{ $letterhead['street'] }}@if($letterhead['street'] && ($letterhead['zip_code'] || $letterhead['city'])), @endif{{ $letterhead['zip_code'] }} {{ $letterhead['city'] }}</p>
        @endif
        @if($letterhead['email'])
            <p>{{ $letterhead['email'] }}</p>
        @endif
    </div>
</div>

<div class="date-line">
    {{ $letterhead['city'] ? $letterhead['city'] . ', ' : '' }}{{ now()->format('d.m.Y') }}
</div>

<div class="details-title">{{ __('export.details', [], $language) }}</div>

<table>
    <thead>
        <tr>
            <th>x</th>
            <th>{{ __('export.artist_name', [], $language) }}</th>
            <th>{{ __('export.arrival_time', [], $language) }}</th>
            <th>{{ __('export.departure_time', [], $language) }}</th>
            <th>{{ __('export.nights_count', [], $language) }}</th>
            <th>{{ __('export.daily_allowance', [], $language) }}</th>
            <th>{{ __('export.total_daily_allowance', [], $language) }}</th>
            <th>{{ __('export.breakfast_count', [], $language) }}</th>
            <th>{{ __('export.breakfast_deduction_total', [], $language) }}</th>
            <th>{{ __('export.payout_per_diem', [], $language) }}</th>
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
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $artistResidency?->artist?->name }}</td>
                <td>{{ $artistResidency->formatted_dates['arrival_date'] }} {{ $artistResidency->formatted_dates['arrival_time'] }}</td>
                <td>{{ $artistResidency->formatted_dates['departure_date'] }} {{ $artistResidency->formatted_dates['departure_time'] }}</td>
                <td style="text-align: center">{{ $days }}</td>
                <td>{{ number_format($artistResidency->daily_allowance, 2, ',', '.') }} €</td>
                <td>{{ number_format($dailyAllowanceTotal, 2, ',', '.') }} €</td>
                <td style="text-align: center">{{ $breakfastCount }}</td>
                <td>{{ number_format($breakfastDeductionTotal, 2, ',', '.') }} €</td>
                <td>{{ number_format($payoutPerDiem, 2, ',', '.') }} €</td>
            </tr>
        @endforeach

        <tr>
            @php
                $grandTotalDailyAllowance = 0;
                $grandTotalBreakfastDeduction = 0;
                $grandTotalPayoutPerDiem = 0;
                foreach ($artistResidencies as $ar) {
                    $d = $ar->days;
                    $dat = $ar->daily_allowance * ($d + $ar->additional_daily_allowance);
                    $bc = $ar->breakfast_count ?? 0;
                    $bdpd = $ar->breakfast_deduction_per_day ?? 0;
                    $bdt = $bc * $bdpd;
                    $grandTotalDailyAllowance += $dat;
                    $grandTotalBreakfastDeduction += $bdt;
                    $grandTotalPayoutPerDiem += ($dat - $bdt);
                }
            @endphp
            <td colspan="4" style="text-align: right; font-weight: bold">{{ __('export.total_days', [], $language) }}:</td>
            <td style="text-align: center; font-weight: bold">{{ $artistResidencies->sum('days') }}</td>
            <td></td>
            <td style="font-weight: bold">{{ number_format($grandTotalDailyAllowance, 2, ',', '.') }} €</td>
            <td></td>
            <td style="font-weight: bold">{{ number_format($grandTotalBreakfastDeduction, 2, ',', '.') }} €</td>
            <td style="font-weight: bold">{{ number_format($grandTotalPayoutPerDiem, 2, ',', '.') }} €</td>
        </tr>
    </tbody>
</table>

<div class="confirmation">
    <h4>{{ __('export.confirmation_title', [], $language) }}</h4>
    <p>{{ __('export.confirmation_text', [], $language) }}</p>
</div>

</body>
</html>
