<?php

namespace Artwork\Modules\ArtistResidency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'civil_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
        ];
    }
}
