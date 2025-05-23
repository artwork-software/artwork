<?php

namespace Artwork\Modules\System\ApiManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
