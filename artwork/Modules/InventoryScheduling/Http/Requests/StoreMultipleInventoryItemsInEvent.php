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
            'events.*.id' => ['required', \Illuminate\Validation\Rule::exists('events', 'id')->whereNull('deleted_at')],
            'events.*.items' => 'required|array|min:1',
            'events.*.items.*.id' => 'required|exists:craft_inventory_items,id',
            // The frontend sends `count` (0 = leave item unbooked); `quantity` was never sent.
            'events.*.items.*.count' => 'required|integer|min:0',
        ];
    }
}
