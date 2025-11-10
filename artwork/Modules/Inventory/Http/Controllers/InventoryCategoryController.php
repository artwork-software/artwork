<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryCategoryRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Repositories\InventoryPropertyRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\InventoryCategoryService;
use Artwork\Modules\Inventory\Services\ProductBasketService;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Room\Models\Room;
use Inertia\Inertia;

class InventoryCategoryController extends Controller
{
    public function __construct(
        protected InventoryCategoryService $categoryService,
        protected InventoryArticleService $articleService,
        protected InventoryPropertyRepository $propertyRepository,
        protected InventoryArticleService $inventoryArticleService,
        protected ProductBasketService $productBasketService,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function index(
        ?InventoryCategory $inventoryCategory = null,
        ?InventorySubCategory $inventorySubCategory = null
    ): \Inertia\Response {
        // Optimiere durch gezieltes Eager Loading
        $inventoryCategory?->load([
            'subcategories' => function ($query): void {
                $query->orderBy('name');
            },
            'subcategories.articles' => function ($query): void {
                $query->orderBy('name');
            },
            'subcategories.properties' => function ($query): void {
                $query->orderBy('name');
            },
            'properties' => function ($query): void {
                $query->orderBy('name');
            }
        ]);

        $inventorySubCategory?->load(['properties' => function ($query): void {
            $query->orderBy('name');
        }, 'articles' => function ($query): void {
            $query->orderBy('name');
        }]);

        $filterableProperties = collect();

        if ($inventoryCategory) {
            $filterableProperties = $inventoryCategory->properties()
                ->filterable()
                ->orderBy('name')
                ->get();
        }

        if ($inventorySubCategory) {
            $subProperties = $inventorySubCategory->properties()
                ->filterable()
                ->orderBy('name')
                ->get();

            $filterableProperties = $filterableProperties
                ->merge($subProperties)
                ->unique('id');
        }

        if (!$inventoryCategory && !$inventorySubCategory) {
            $filterableProperties = $this->propertyRepository->filterable();
        }

        $articles = $this->articleService->getArticleList(
            $inventoryCategory,
            $inventorySubCategory,
            request('search')
        );

        return Inertia::render('Inventory/Index', [
            'categories' => $this->categoryService->getAllWithRelations(),
            'currentCategory' => $inventoryCategory,
            'currentSubCategory' => $inventorySubCategory,
            'articles' => $articles,
            'articlesCount' => $this->articleService->count(),
            'filterableProperties' => $filterableProperties->values(), // Reset keys
            'properties' => $this->propertyRepository->all(),
            'rooms' => Room::select('id', 'name')->orderBy('name')->get(),
            'manufacturers' => Manufacturer::select('id', 'name')->orderBy('name')->get(),
            'statuses' => InventoryArticleStatus::select('id', 'name', 'color')->orderBy('order')->get(),
            'countsByStatus' => $this->articleService->getCountsByStatus($articles),
            'productBaskets' => $this->productBasketService->getUserBasket(),
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
            'rooms' => Room::all(),
            'manufacturers' => Manufacturer::all(),
        ]);
    }

    public function destroy(InventoryCategory $inventoryCategory): void
    {
        $inventoryCategory->articles()->each(function (InventoryArticle $article): void {
            $this->inventoryArticleService->delete($article);
            $this->inventoryArticleService->forceDelete($article);
        });

        $inventoryCategory->properties()->detach();
        $inventoryCategory->subcategories()->each(function (InventorySubCategory $subcategory): void {
            $subcategory->articles()->each(function (InventoryArticle $article): void {
                $this->inventoryArticleService->delete($article);
                $this->inventoryArticleService->forceDelete($article);
            });
            $subcategory->properties()->detach();
            $subcategory->delete();
        });

        $inventoryCategory->delete();
    }

    public function getAllCategories()
    {
        // Optimiere durch spezifisches Select und Eager Loading
        $categories = InventoryCategory::with([
            'subcategories:id,inventory_category_id,name',
            'subcategories.properties:id,name,type,select_values',
            'articles:id,name,inventory_category_id,inventory_sub_category_id',
            'articles.category:id,name',
            'articles.subCategory:id,name'
        ])
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }
}
