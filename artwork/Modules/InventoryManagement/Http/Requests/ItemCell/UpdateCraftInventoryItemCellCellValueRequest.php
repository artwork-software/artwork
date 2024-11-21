<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\ItemCell;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftInventoryItemCellCellValueRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cell_value' => 'nullable|string',
        ];
    }
}
