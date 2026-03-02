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
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.total_daily_allowance', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.breakfast_count', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.breakfast_deduction_total', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.payout_per_diem', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.cost_per_night', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.total', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.get_sum', [], $language) }}</th>
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
            <td>{{ $artistResidency->display_name }}</td>
            <td>{{ $artistResidency->formatted_dates['arrival_date'] }} {{ $artistResidency->formatted_dates['arrival_time'] }}</td>
            <td>{{ $artistResidency->formatted_dates['departure_date'] }} {{ $artistResidency->formatted_dates['departure_time'] }}</td>
            <td style="text-align: center">{{ $days }}</td>
            <td data-format="0.00">{{ $artistResidency->daily_allowance }}</td>
            <td style="text-align: center">{{ $artistResidency->additional_daily_allowance }}</td>
            <td data-format="0.00">{{ $dailyAllowanceTotal }}</td>
            <td style="text-align: center">{{ $breakfastCount }}</td>
            <td data-format="0.00">{{ $breakfastDeductionTotal }}</td>
            <td data-format="0.00">{{ $payoutPerDiem }}</td>
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
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
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
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: right; font-weight: bold; background-color: #cccccc">{{ __('export.total_days', [], $language) }}:</td>
        <td style="text-align: center; text-decoration-line: underline; background-color: #cccccc">{{ $artistResidencies->sum('days') }}</td>
        <td></td>
        <td></td>
        <td style="text-decoration-line: underline; background-color: #cccccc" data-format="0.00">{{ $grandTotalDailyAllowance }}</td>
        <td></td>
        <td style="text-decoration-line: underline; background-color: #cccccc" data-format="0.00">{{ $grandTotalBreakfastDeduction }}</td>
        <td style="text-decoration-line: underline; background-color: #cccccc" data-format="0.00">{{ $grandTotalPayoutPerDiem }}</td>
        <td></td>
        <td style="text-decoration-line: underline; background-color: #cccccc" data-format="0.00">{{ $grandTotal }}</td>
        <td></td>
    </tr>
    </tbody>
</table>
