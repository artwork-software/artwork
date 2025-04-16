<?php

namespace Artwork\Modules\Accommodation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ];
    }
}
