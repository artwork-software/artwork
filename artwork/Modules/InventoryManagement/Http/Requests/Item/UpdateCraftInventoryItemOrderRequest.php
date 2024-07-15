<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftInventoryItemOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['order' => 'required|integer'];
    }
}
