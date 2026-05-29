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
     * Generic structural validation only. Per-component-type schema validation is
     * intentionally out of scope (see work package); the client sends structured
     * data, defensive normalization happens in the service.
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
