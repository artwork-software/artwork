<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class CreateCraftInventoryGroupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoryId' => 'required|integer|exists:craft_inventory_categories,id',
            'order' => 'required|integer',
            'name' => 'required|string'
        ];
    }
}
