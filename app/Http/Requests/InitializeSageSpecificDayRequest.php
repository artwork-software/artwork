<?php

namespace App\Http\Requests;

use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InitializeSageSpecificDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateInterfaceSettings', SageApiSettings::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ktr' => ['nullable', 'string', 'max:100', 'regex:/\A[A-Za-z0-9._\-\/ ]+\z/u', 'required_without:specificDayFrom'],
            'specificDayFrom' => ['nullable', 'date_format:Y-m-d', 'required_without:ktr'],
            'specificDayTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:specificDayFrom'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $ktr = $this->input('ktr');

        $this->merge([
            'ktr' => is_string($ktr) && trim($ktr) !== '' ? trim($ktr) : null,
            'specificDayFrom' => $this->input('specificDayFrom') ?: null,
            'specificDayTo' => $this->input('specificDayTo') ?: null,
        ]);
    }
}
