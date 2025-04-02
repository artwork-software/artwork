<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryCategoryRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Repositories\InventoryPropertyRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\InventoryCategoryService;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Room\Models\Room;
use Inertia\Inertia;

class InventoryCategoryController extends Controller
{
    public function __construct(
        protected InventoryCategoryService $categoryService,
        protected InventoryArticleService $articleService,
        protected InventoryPropertyRepository $propertyRepository,
        protected InventoryArticleService $inventoryArticleService
    ) {}

    /**
     * @throws \JsonException
     */
    public function index(
        ?InventoryCategory $inventoryCategory = null,
        ?InventorySubCategory $inventorySubCategory = null
    ): \Inertia\Response {
        $inventoryCategory?->load(['subcategories', 'properties']);
        $inventorySubCategory?->load(['properties']);

        $filterableProperties = collect();

        if ($inventoryCategory) {
            $filterableProperties = $inventoryCategory->properties()->filterable()->get();
        }

        if ($inventorySubCategory) {
            $filterableProperties = $filterableProperties
                ->merge($inventorySubCategory->properties()->filterable()->get())
                ->unique('id');
        }

        if (!$inventoryCategory && !$inventorySubCategory) {
            $filterableProperties = $this->propertyRepository->filterable();
        }

        return Inertia::render('Inventory/Index', [
            'categories' => $this->categoryService->getAllWithRelations(),
            'currentCategory' => $inventoryCategory,
            'currentSubCategory' => $inventorySubCategory,
            'articles' => $this->articleService->getArticleList($inventoryCategory, $inventorySubCategory, request('search')),
            'articlesCount' => $this->articleService->count(),
            'filterableProperties' => $filterableProperties,
            'properties' => $this->propertyRepository->all(),
            'rooms' => Room::all(),
            'manufacturers' => Manufacturer::all(),
        ]);
    }

    public function store(StoreInventoryCategoryRequest $request): void
    {
        $this->categoryService->createWithRelations(
            $request->validated(),
            $request->collect('properties'),
            $request->collect('subcategories')
        );
    }

    public function update(UpdateInventoryCategoryRequest $request, InventoryCategory $inventoryCategory): void
    {
        $this->categoryService->updateWithRelations(
            $inventoryCategory,
            $request->validated(),
            $request->collect('properties'),
            $request->collect('subcategories')
        );
    }

    public function settings(): \Inertia\Response
    {
        return Inertia::render('InventorySetting/Categories', [
            'categories' => $this->categoryService->paginateWithRelations(),
            'properties' => $this->propertyRepository->all(),
        ]);
    }

    public function destroy(InventoryCategory $inventoryCategory): void
    {
        $inventoryCategory->articles()->each(function (InventoryArticle $article) {
            $this->inventoryArticleService->delete($article);
            $this->inventoryArticleService->forceDelete($article);
        });

        $inventoryCategory->properties()->detach();
        $inventoryCategory->subcategories()->each(function (InventorySubCategory $subcategory) {
            $subcategory->articles()->each(function (InventoryArticle $article) {
                $this->inventoryArticleService->delete($article);
                $this->inventoryArticleService->forceDelete($article);
            });
            $subcategory->properties()->detach();
            $subcategory->delete();
        });

        $inventoryCategory->delete();
    }
}
