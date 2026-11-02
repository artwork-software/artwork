<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Foundation\Http\FormRequest;

/** The article list: a search term, a category or subcategory, and the page. */
class AppInventoryArticlesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:inventory_sub_categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('q', ''));
    }

    public function category(): ?InventoryCategory
    {
        return $this->filled('category_id') ? InventoryCategory::query()->find($this->integer('category_id')) : null;
    }

    public function subCategory(): ?InventorySubCategory
    {
        return $this->filled('subcategory_id')
            ? InventorySubCategory::query()->find($this->integer('subcategory_id'))
            : null;
    }
}
