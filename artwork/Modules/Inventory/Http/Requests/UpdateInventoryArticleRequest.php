<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Passe ggf. Rechte hier an
    }

    public function rules(): array
    {
        $articleId = $this->route('inventoryArticle')?->id;

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
            'detailed_article_quantities.*.id' => [
                'nullable',
                'integer',
                Rule::exists('inventory_detailed_quantity_articles', 'id')
                    ->where(fn ($query) => $query->where('inventory_article_id', $articleId)
                        ->whereNull('deleted_at')),
            ],
            'detailed_article_quantities.*.name' => ['required', 'string', 'max:255'],
            'detailed_article_quantities.*.quantity' => ['required', 'integer', 'min:0'],
            'detailed_article_quantities.*.description' => ['nullable', 'string'],
            'detailed_article_quantities.*.properties' => ['nullable', 'array'],
            'detailed_article_quantities.*.properties.*.id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'detailed_article_quantities.*.properties.*.value' => ['nullable', 'max:255'],

            'statusValues' => ['nullable', 'array'],
            'statusValues.*.id' => ['required', 'integer', 'exists:inventory_article_statuses,id'],


            // 🔹 NEU: Tags am Artikel
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:inventory_tags,id'],
        ];
    }
}
