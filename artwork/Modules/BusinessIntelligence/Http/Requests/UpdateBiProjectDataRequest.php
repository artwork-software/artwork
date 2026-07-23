<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiProjectDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitors_total' => ['nullable', 'integer', 'min:0'],
            'visitors_not_applicable' => ['boolean'],
            'sold_tickets_total' => ['nullable', 'integer', 'min:0'],
            'sold_tickets_not_applicable' => ['boolean'],
            'revenue_total' => ['nullable', 'numeric', 'min:0'],
            'revenue_not_applicable' => ['boolean'],
            'costs_total' => ['nullable', 'numeric', 'min:0'],
            'costs_not_applicable' => ['boolean'],
            'is_new_production' => ['boolean'],
            'is_co_production' => ['boolean'],
            'is_own_production' => ['boolean'],
            'is_germany_premiere' => ['boolean'],
            'premiere_date' => ['nullable', 'date'],
            'scope' => ['nullable', 'in:actual,plan'],
            // Herkunft eines per Vorschlags-Button übernommenen Umsatz-/Kostenwerts
            'revenue_source' => ['nullable', 'in:budget_income,sage'],
            'costs_source' => ['nullable', 'in:budget_expense,sage'],
        ];
    }
}
