<?php

namespace Artwork\Modules\Inventory\Services;


use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Support\Facades\Request;

class InventoryArticleService
{

    public function __construct(protected InventoryArticleRepository $articleRepository) {}

    /**
     * @throws \JsonException
     */
    public function getArticleList(?InventoryCategory $category = null, ?InventorySubCategory $subCategory = null, ?string $search = '')
    {
        $query = $this->articleRepository->baseQuery();

        if ($search) {
            $ids = $this->articleRepository->search($search)->pluck('id');
            $query->whereIn('id', $ids);
        }

        if ($category && !$subCategory && !$search) {
            $query = $category->articles();
        } elseif ($category && $subCategory && !$search) {
            $query = $subCategory->articles();
        }

        $filters = json_decode(Request::get('filters', '[]'), true, 512, JSON_THROW_ON_ERROR);

        $query = $this->articleRepository->applyFilters($query, $filters);

        return $this->articleRepository->withRelations($query, Request::integer('entitiesPerPage', 50));
    }

    public function count(): int
    {
        return $this->articleRepository->count();
    }
}