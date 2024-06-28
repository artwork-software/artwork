<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryManagementExportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => 'required|array|min:1',
            'data.*.craftId' => 'required|numeric|exists:crafts,id',
            'data.*.craftName' => 'required|string',
            'data.*.abbreviation' => 'required|string',
            'data.*.filteredInventoryCategories' => 'array',
            'data.*.filteredInventoryCategories.*.groups' => 'array',
            'data.*.filteredInventoryCategories.*.groups.*.items' => 'array',
            'data.*.filteredInventoryCategories.*.groups.*.items.*.cells' => 'array'
        ];
    }
}
