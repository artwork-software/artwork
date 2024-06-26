<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class CreateCraftInventoryItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'groupId' => 'required|integer|exists:craft_inventory_groups,id',
            'order' => 'required|integer',
        ];
    }
}
