<?php

namespace Artwork\Modules\Budget\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetManagementAccountRequest extends FormRequest
{
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
