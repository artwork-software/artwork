<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalComponentValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The 'external' route middleware authenticated the external guard and
        // 'external.scoped:write' verified the write scope on this tab.
        return $this->user('external') !== null;
    }

    /**
     * Structural validation only; the per-type check (shared with the internal endpoint)
     * happens in ProjectComponentValueNormalizer via ExternalComponentValueService.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
        ];
    }
}
