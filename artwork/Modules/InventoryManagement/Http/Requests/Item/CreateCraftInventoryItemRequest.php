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
            'groupId' => 'nullable|integer|exists:craft_inventory_groups,id',
            'folderId' => 'nullable|integer|exists:craft_inventory_group_folders,id',
            'order' => 'required|integer',
        ];
    }
}
