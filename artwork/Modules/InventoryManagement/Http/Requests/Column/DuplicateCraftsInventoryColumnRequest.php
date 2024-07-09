<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateCraftsInventoryColumnRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'columnId' => 'required|exists:crafts_inventory_columns,id'
        ];
    }
}
