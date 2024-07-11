<?php

namespace Artwork\Modules\InventoryScheduling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMultipleInventoryItemsInEvent extends FormRequest
{
    /**
     * @return array<string, string>
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
