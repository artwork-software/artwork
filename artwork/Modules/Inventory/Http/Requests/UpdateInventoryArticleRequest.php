<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
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
        $maxImageSizeKb = app(GeneralSettings::class)->inventory_article_image_max_size_mb * 1024;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'inventory_sub_category_id' => ['nullable', 'integer', 'exists:inventory_sub_categories,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_detailed_quantity' => ['required', 'boolean'],

            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:' . $maxImageSizeKb],
            'newImages' => ['nullable', 'array'],
            // Laravel's 'image' rule plus HEIC/HEIF (iPhone photos) — those
            // get converted to JPEG on upload (InventoryArticleImageService).
            'newImages.*' => ['mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,heif', 'max:' . $maxImageSizeKb],

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
