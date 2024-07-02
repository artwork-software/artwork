<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Filter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrCreateInventoryFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filter' => 'array',
            'filter.*.craftId' => 'exists:crafts,id'
        ];
    }

    public function collect($key = null)
    {
        return parent::collect($key)->map(fn($filter) => $filter['craftId']);
    }
}
