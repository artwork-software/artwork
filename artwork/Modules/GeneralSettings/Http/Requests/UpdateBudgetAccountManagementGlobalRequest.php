<?php

namespace Artwork\Modules\GeneralSettings\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetAccountManagementGlobalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //Handled by controller and GeneralSettingsPolicy updateBudgetAccountManagementGlobalSetting
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
            'enabled' => 'boolean'
        ];
    }
}
