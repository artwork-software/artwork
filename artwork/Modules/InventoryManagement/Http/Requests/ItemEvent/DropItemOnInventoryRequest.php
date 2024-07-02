<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\ItemEvent;

use Illuminate\Foundation\Http\FormRequest;

class DropItemOnInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer',
            'events' => 'required|array|min:1',
            'events.*' => 'required|integer|exists:events,id',
        ];
    }
}
