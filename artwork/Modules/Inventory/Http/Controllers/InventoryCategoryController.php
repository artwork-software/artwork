<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryCategoryRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleFilterPreset;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\Inventory\Repositories\InventoryPropertyRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleFilterResolver;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\InventoryCategoryService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterService;
use Artwork\Modules\Inventory\Services\ProductBasketService;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryCategoryController extends Controller
{
    public function __construct(
        protected InventoryCategoryService $categoryService,
        protected InventoryArticleService $articleService,
        protected InventoryPropertyRepository $propertyRepository,
        protected InventoryArticleService $inventoryArticleService,
        protected ProductBasketService $productBasketService,
        protected InventoryUserFilterService $filterService,
        protected InventoryArticleFilterResolver $filterResolver,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function index(
        ?InventoryCategory $inventoryCategory = null,
        ?InventorySubCategory $inventorySubCategory = null
    ): \Inertia\Response {
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

        $resolved = $this->filterResolver->resolve($inventoryCategory?->id, $inventorySubCategory?->id);
        $statusId = request()->integer('status_id') ?: null;

        $articles = $this->articleService->getArticleList(
            $inventoryCategory,
            $inventorySubCategory,
            request('search'),
            $resolved['filters'],
            $resolved['tag_ids'],
            $statusId,
        );

        $articles->appends([
            'filters' => json_encode($resolved['filters']),
            'tag_ids' => $resolved['tag_ids'],
            'filter_preset_id' => $resolved['filter_preset_id'],
            'status_id' => $statusId,
        ]);

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
            'countsByStatus' => $this->articleService->getCountsByStatusAggregated(
                $inventoryCategory,
                $inventorySubCategory,
                request('search'),
                $resolved['filters'],
                $resolved['tag_ids'],
            ),
            'activeStatusId' => $statusId,
            'productBaskets' => $this->productBasketService->getUserBasket(),
            'tagGroups' => InventoryTagGroup::with([
                'tags' => function ($query): void {
                    $query->with(['allowedUsers', 'allowedDepartments'])
                        ->orderBy('position');
                }
            ])->orderBy('position')->get(),
            'tags' => InventoryTag::with(['allowedUsers', 'allowedDepartments'])
                ->orderBy('position')
                ->get(),
            'inventoryGridLayout' => auth()->user()->inventory_grid_layout ?? true,
            'filterPresets' => InventoryArticleFilterPreset::query()
                ->where('user_id', auth()->id())
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id','name','is_default','inventory_category_id','inventory_sub_category_id']),

            'appliedFilters' => $resolved['filters'],
            'appliedTagIds' => $resolved['tag_ids'],
            'activeFilterPresetId' => $resolved['filter_preset_id'],
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
        $user = Auth::user();

        // Get filtered article IDs based on user's saved filters (including tags)
        $filteredArticleIds = $this->filterService
            ->getFilteredArticlesNew($user)
            ->pluck('id')
            ->toArray();

        // Load categories with filtered articles
        $categories = InventoryCategory::with([
            'subcategories:id,inventory_category_id,name',
            'subcategories.properties:id,name,type,select_values',
            'articles' => function ($query) use ($filteredArticleIds) {
                if (!empty($filteredArticleIds)) {
                    $query->whereIn('id', $filteredArticleIds);
                }
                $query->select('id', 'name', 'inventory_category_id', 'inventory_sub_category_id');
            },
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

    public function updateInventoryGridLayout(\Illuminate\Http\Request $request): void
    {
        $validated = $request->validate([
            'inventory_grid_layout' => 'required|boolean'
        ]);

        auth()->user()->update([
            'inventory_grid_layout' => $validated['inventory_grid_layout']
        ]);


    }
}
