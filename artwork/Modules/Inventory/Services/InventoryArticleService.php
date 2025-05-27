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
            'inventory_sub_category_id' => $request->filled('inventory_sub_category_id')
                ? $request->integer('inventory_sub_category_id')
                : null,
            'quantity' => $request->integer('quantity'),
            'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
        ]);



        $images = $request->file('newImages') ?? [];
        $mainImageIndex = $request->integer('main_image_index');

        $this->articleRepository->addImages($article, $images, $mainImageIndex);

        $this->articleRepository->attachProperties($article, $request->collect('properties'));
        $this->articleRepository->addDetailedArticles($article, $request->collect('detailed_article_quantities'));

        // Statuswerte anhängen
        $statusValues = $request->get('statusValues');
        if (is_array($statusValues) && count($statusValues) > 0) {
            $this->articleRepository->attachStatusValues($article, $statusValues);
        }

        return $article;
    }


    public function update(InventoryArticle $article, UpdateInventoryArticleRequest $request): ?InventoryArticle
    {
        $data = [
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'inventory_category_id' => $request->integer('inventory_category_id'),
            'quantity' => $request->integer('quantity'),
            'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
        ];
        if ($request->get('inventory_sub_category_id')) {
            $data['inventory_sub_category_id'] = $request->integer('inventory_sub_category_id');
        }
        $this->articleRepository->update($article, $data);

        // Entfernen selektiv markierter Bilder (wenn IDs übergeben wurden)
        $removedImageIds = $request->get('removed_image_ids');
        if (is_array($removedImageIds) && count($removedImageIds) > 0) {
            $article->images()->whereIn('id', $removedImageIds)->delete();
        }

        // Neue Bilder hinzufügen, falls welche hochgeladen wurden
        $images = $request->file('newImages') ?? [];
        if (count($images) > 0) {
            $mainImageIndex = $request->integer('main_image_index');
            $this->articleRepository->addImages($article, $images, $mainImageIndex);
        }

        // Alte Properties und detaillierte Artikel-Informationen entfernen
        $this->articleRepository->detachAllProperties($article);
        $this->articleRepository->detachAllDetailedArticleProperties($article);
        $this->articleRepository->deleteAllDetailedArticles($article);

        $article = $article?->fresh();

        // Neue Properties und detaillierte Artikel-Informationen anhängen
        $this->articleRepository->attachProperties($article, $request->collect('properties'));
        $this->articleRepository->addDetailedArticles($article, $request->collect('detailed_article_quantities'));

        // Statuswerte anhängen
        $statusValues = $request->get('statusValues');
        if (is_array($statusValues) && count($statusValues) > 0) {
            $this->articleRepository->detachAllStatusValues($article);
            $this->articleRepository->attachStatusValues($article, $statusValues);
        }

        return $article;
    }

    public function delete(InventoryArticle $article): void
    {
        $this->articleRepository->delete($article);
    }

    public function getAllTrashed(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->articleRepository->getAllTrashed();
    }

    public function forceDelete(InventoryArticle $article): void
    {
        $this->articleRepository->forceDelete($article);
    }

    public function restore(InventoryArticle $article): void
    {
        $this->articleRepository->restore($article);
    }

    public function attachStatusValues(InventoryArticle $article, array $statusValues): void
    {
        $this->articleRepository->attachStatusValues($article, $statusValues);
    }


    public function getAvailableStock(InventoryArticle $article, string $startDate, string $endDate): array
    {
        return $this->articleRepository->getAvailableStock($article, $startDate, $endDate);
    }
}
