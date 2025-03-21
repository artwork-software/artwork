<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryArticleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'inventory_sub_category_id' => ['nullable', 'integer', 'exists:inventory_sub_categories,id'],
            'inventory_article_images' => ['nullable', 'array'],
            'quantity' => ['required', 'integer'],
            'properties' => ['nullable', 'array'],
            'properties.*.id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'properties.*.value' => ['nullable', 'string', 'max:255'],
            'main_image_index' => ['required', 'integer'],
        ];
    }
}
