<?php

namespace Artwork\Modules\Budget\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetManagementCostUnitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'cost_unit_number' => 'string',
            'title' => 'string'
        ];
    }
}
