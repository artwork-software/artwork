<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
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
        $maxImageSizeKb = app(GeneralSettings::class)->inventory_article_image_max_size_mb * 1024;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'inventory_sub_category_id' => ['nullable', 'integer', 'exists:inventory_sub_categories,id'],
            'inventory_article_images' => ['nullable', 'array'],
            'newImages' => ['nullable', 'array'],
            // Laravel's 'image' rule plus HEIC/HEIF (iPhone photos) — those
            // get converted to JPEG on upload (InventoryArticleImageService).
            'newImages.*' => ['mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,heif', 'max:' . $maxImageSizeKb],
            'quantity' => ['required', 'integer'],
            'properties' => ['nullable', 'array'],
            'properties.*.id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'properties.*.value' => ['nullable', 'max:255'],
            'main_image_index' => ['required', 'integer'],
            'statusValues' => ['nullable', 'array'],
            'statusValues.*.id' => ['required', 'integer', 'exists:inventory_article_statuses,id'],


            // 🔹 NEU: Tags am Artikel
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:inventory_tags,id'],

        ];
    }
}
