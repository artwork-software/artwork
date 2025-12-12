<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryUserFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category_ids' => 'array',
            'category_ids.*' => 'integer',
            'sub_category_ids' => 'array',
            'sub_category_ids.*' => 'integer',
            'property_filters' => 'array',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer',
        ];
    }
}
