<table>
    <thead>
        <tr>
            <th style="background-color: #cccccc; font-weight: bold;">#</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.contract_name', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.contract_partner', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.project', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.contract_type', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.company_type', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.amount', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.currency', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.description', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.ksk_liable', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.ksk_amount', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.ksk_reason', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.foreign_tax', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.foreign_tax_amount', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.foreign_tax_reason', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.reverse_charge_amount', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.deadline_date', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.creator', [], $language) }}</th>
            <th style="background-color: #cccccc; font-weight: bold;">{{ __('export.created_at', [], $language) }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($contracts as $contract)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $contract->name }}</td>
            <td>{{ $contract->contract_partner }}</td>
            <td>{{ $contract->project?->name }}</td>
            <td>{{ $contract->contract_type?->name }}</td>
            <td>{{ $contract->company_type?->name }}</td>
            <td data-format="0.00">{{ $contract->amount }}</td>
            <td>{{ $contract->currency?->name }}</td>
            <td>{{ $contract->description }}</td>
            <td>{{ $contract->ksk_liable ? __('export.yes', [], $language) : __('export.no', [], $language) }}</td>
            <td data-format="0.00">{{ $contract->ksk_amount }}</td>
            <td>{{ $contract->ksk_reason }}</td>
            <td>{{ $contract->foreign_tax ? __('export.yes', [], $language) : __('export.no', [], $language) }}</td>
            <td data-format="0.00">{{ $contract->foreign_tax_amount }}</td>
            <td>{{ $contract->foreign_tax_reason }}</td>
            <td data-format="0.00">{{ $contract->reverse_charge_amount }}</td>
            <td>{{ $contract->deadline_date?->format('d.m.Y') }}</td>
            <td>{{ $contract->creator?->full_name }}</td>
            <td>{{ $contract->created_at?->format('d.m.Y H:i') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
