<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCrmSelfEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The 'external' route middleware already authenticated the external guard.
        return $this->user('external') !== null;
    }

    /**
     * Structural validation only. Schema-aware required-field checks live in the service.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'values' => ['required', 'array'],
            'values.*' => ['array'],
            'values.*.*' => ['nullable'],
        ];
    }
}
