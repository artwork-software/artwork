<?php

namespace Artwork\Modules\System\ApiManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Rechteprüfung passiert im Controller über die TokenPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'scopes' => 'array',
            'scopes.*' => 'string',
        ];
    }
}
