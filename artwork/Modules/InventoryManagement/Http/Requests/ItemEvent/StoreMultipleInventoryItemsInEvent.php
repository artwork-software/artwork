<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\ItemEvent;

use Illuminate\Foundation\Http\FormRequest;

class StoreMultipleInventoryItemsInEvent extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => 'required|array|min:1',
            'events.*.id' => 'required|exists:events,id',
            'events.*.items' => 'required|array|min:1',
            'events.*.items.*.id' => 'required|exists:craft_inventory_items,id',
            'events.*.items.*.quantity' => 'nullable|integer',
        ];
    }
}
