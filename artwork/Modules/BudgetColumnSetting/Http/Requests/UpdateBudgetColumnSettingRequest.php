<?php

namespace Artwork\Modules\BudgetColumnSetting\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetColumnSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        //handled by policy in BudgetGeneralController
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'column_name' => 'string'
        ];
    }
}
