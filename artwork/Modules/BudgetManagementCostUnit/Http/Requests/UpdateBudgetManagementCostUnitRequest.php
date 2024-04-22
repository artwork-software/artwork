<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetManagementCostUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        //handled in controller
        return true;
    }

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
