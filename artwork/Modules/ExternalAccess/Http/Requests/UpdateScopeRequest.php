<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'valid_to' => ['nullable', 'date', 'after:now'],
            'access_type' => ['nullable', Rule::enum(ExternalAccessType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (!$this->filled('valid_to') && !$this->filled('access_type')) {
                $v->errors()->add('valid_to', __('At least one field must be provided.'));
            }
        });
    }
}
