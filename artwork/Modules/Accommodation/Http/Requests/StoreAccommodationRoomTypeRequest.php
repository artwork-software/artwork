<?php

namespace Artwork\Modules\Accommodation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRoomTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Zimmertypen sind Unterkunfts-Stammdaten — gleiches Recht wie Unterkunft anlegen.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \Artwork\Modules\Accommodation\Models\Accommodation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:accommodation_room_types,name',
        ];
    }
}
