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
            'sold_tickets_total' => ['nullable', 'integer', 'min:0'],
            'revenue_total' => ['nullable', 'numeric', 'min:0'],
            'is_new_production' => ['boolean'],
            'is_co_production' => ['boolean'],
            'is_own_production' => ['boolean'],
            'is_germany_premiere' => ['boolean'],
            'premiere_date' => ['nullable', 'date'],
        ];
    }
}
