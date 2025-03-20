<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:55'],
            'properties' => ['nullable', 'array'],
            'properties.*.id' => ['required', 'exists:inventory_article_properties,id'],
            'properties.*.defaultValue' => ['nullable'],
            'subcategories' => ['array'],
            'subcategories.*.name' => ['required', 'string', 'max:55'],
            'subcategories.*.properties' => ['nullable', 'array'],
            'subcategories.*.properties.*.id' => ['required', 'exists:inventory_article_properties,id'],
        ];
    }
}
