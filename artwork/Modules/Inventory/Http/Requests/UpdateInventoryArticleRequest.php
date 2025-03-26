<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Passe ggf. Rechte hier an
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_detailed_quantity' => ['required', 'boolean'],

            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:10240'], // max 10MB

            'main_image_index' => ['nullable', 'integer'],

            'properties' => ['nullable', 'array'],
            'properties.*.id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'properties.*.value' => ['nullable', 'max:255'],

            'detailed_article_quantities' => ['nullable', 'array'],
            'detailed_article_quantities.*.name' => ['required', 'string', 'max:255'],
            'detailed_article_quantities.*.quantity' => ['required', 'integer', 'min:0'],
            'detailed_article_quantities.*.description' => ['nullable', 'string'],
            'detailed_article_quantities.*.properties' => ['nullable', 'array'],
            'detailed_article_quantities.*.properties.*.id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'detailed_article_quantities.*.properties.*.value' => ['nullable', 'max:255'],
        ];
    }
}
