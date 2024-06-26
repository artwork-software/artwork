<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CreateCraftInventoryCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'craftId' => 'required|integer|exists:crafts,id',
            'name' => 'required|string',
            'order' => 'required|integer',
        ];
    }
}
