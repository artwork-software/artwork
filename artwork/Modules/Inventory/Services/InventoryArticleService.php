<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Support\Facades\Request;

class InventoryArticleService
{

    public function __construct(protected InventoryArticleRepository $articleRepository)
    {
    }

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

    public function store(StoreInventoryArticleRequest $request): ?InventoryArticle
    {
        $article = $this->articleRepository->create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'inventory_category_id' => $request->integer('inventory_category_id'),
            'inventory_sub_category_id' => $request->integer('inventory_sub_category_id'),
            'quantity' => $request->integer('quantity'),
            'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
        ]);

        $images = $request->file('images') ?? [];
        $mainImageIndex = $request->integer('main_image_index');

        $this->articleRepository->addImages($article, $images, $mainImageIndex);

        $article = $article->fresh();

        $this->articleRepository->attachProperties($article, $request->collect('properties'));
        $this->articleRepository->addDetailedArticles($article, $request->collect('detailed_article_quantities'));

        return $article;
    }


    public function update(InventoryArticle $article, UpdateInventoryArticleRequest $request): ?InventoryArticle
    {
        if ($request->get('inventory_sub_category_id')) {
            $data = [
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'inventory_category_id' => $request->integer('inventory_category_id'),
                'inventory_sub_category_id' => $request->integer('inventory_sub_category_id'),
                'quantity' => $request->integer('quantity'),
                'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
            ];
        } else {
            $data = [
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'inventory_category_id' => $request->integer('inventory_category_id'),
                'quantity' => $request->integer('quantity'),
                'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
            ];
        }


        $this->articleRepository->update($article, $data);

        // Remove old images
        $article->images()->delete();

        // Re-upload images
        $images = $request->file('images') ?? [];
        $mainImageIndex = $request->integer('main_image_index');
        $this->articleRepository->addImages($article, $images, $mainImageIndex);

        // Detach old properties
        $this->articleRepository->detachAllProperties($article);
        $this->articleRepository->detachAllDetailedArticleProperties($article);

        // Remove old detailed articles
        $this->articleRepository->deleteAllDetailedArticles($article);

        $article = $article?->fresh();

        $this->articleRepository->attachProperties($article, $request->collect('properties'));
        $this->articleRepository->addDetailedArticles($article, $request->collect('detailed_article_quantities'));

        return $article;
    }
}
