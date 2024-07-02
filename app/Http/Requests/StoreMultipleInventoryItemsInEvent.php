<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMultipleInventoryItemsInEvent extends FormRequest
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
            'events' => 'required|array|min:1',
            'events.*' => 'required|exists:events,id',
            'events.*.items' => 'required|array|min:1',
            'events.*.items.*' => 'required|exists:craft_inventory_items,id',
            'events.*.items.*.quantity' => 'nullable|integer',
        ];
    }
}
