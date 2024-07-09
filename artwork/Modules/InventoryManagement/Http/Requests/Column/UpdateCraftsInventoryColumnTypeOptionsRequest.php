<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftsInventoryColumnTypeOptionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selectOptions' => 'required|array|min:1',
            'selectOptions.*' => 'required|string'
        ];
    }
}
