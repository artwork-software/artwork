<?php

namespace Artwork\Modules\BudgetManagementAccount\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetManagementAccountRequest extends FormRequest
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
            'account_number' => 'string',
            'title' => 'string',
            'is_account_for_revenue' => 'boolean'
        ];
    }
}
