<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftInventoryCategoryOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['order' => 'required|integer'];
    }
}
