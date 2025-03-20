<?php

namespace App\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryCategoryRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Inertia\Inertia;

class InventoryCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Inventory/Index', [
            'categories' => InventoryCategory::with(['subcategories', 'subcategories.properties', 'properties'])->get(),
            'articles' => InventoryArticle::paginate(50),
            'articlesCount' => InventoryArticle::count(),
            'filterableProperties' => InventoryArticleProperties::filterable()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryCategoryRequest $request)
    {
        $properties = $request->collect('properties');
        $subcategories = $request->collect('subcategories');
        $category = InventoryCategory::create($request->validated());

        // attach properties to category with defaultValue as value
        foreach ($properties as $property) {
            $category->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
        }

        foreach ($subcategories as $subcategory) {
            $subCategory = $category->subcategories()->create($subcategory);
            foreach ($subcategory['properties'] as $property) {
                $subCategory->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryCategory $inventoryCategory): \Inertia\Response
    {
        $inventoryCategory->load(['subcategories', 'subcategories.properties', 'articles', 'properties']);

        $filterableProperties = $inventoryCategory->properties()->filterable()->get();

        return Inertia::render('Inventory/Index', [
            'categories' => InventoryCategory::with(['subcategories', 'properties'])->get(),
            'currentCategory' => $inventoryCategory,
            'articles' => $inventoryCategory->articles()->paginate(50),
            'articlesCount' => InventoryArticle::count(),
            'filterableProperties' => $filterableProperties,
        ]);
    }

    public function showSubCategory(InventoryCategory $inventoryCategory, InventorySubCategory $subCategory): \Inertia\Response
    {
        $inventoryCategory->load(['subcategories', 'properties', 'articles']);
        $subCategory->load(['properties', 'articles']);

        $filterablePropertiesCategory = $inventoryCategory->properties()->filterable()->get();
        $filterablePropertiesSubCategory = $subCategory->properties()->filterable()->get();

        $filterableProperties = $filterablePropertiesCategory->merge($filterablePropertiesSubCategory)->unique('id');

        return Inertia::render('Inventory/Index', [
            'categories' => InventoryCategory::with('subcategories')->get(),
            'currentCategory' => $inventoryCategory,
            'currentSubCategory' => $subCategory,
            'articles' => $subCategory->articles()->paginate(50),
            'articlesCount' => InventoryArticle::count(),
            'filterableProperties' => $filterableProperties,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryCategory $inventoryCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryCategoryRequest $request, InventoryCategory $inventoryCategory)
    {
        $inventoryCategory->update($request->validated());
        $properties = $request->collect('properties');
        $subcategories = $request->collect('subcategories');
        // detach all properties
        $inventoryCategory->properties()->detach();

        foreach ($properties as $property) {
            $inventoryCategory->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
        }

        foreach ($subcategories as $subcategory) {
            $subcategory['inventory_category_id'] = $inventoryCategory->id;
            $subCategoryUpdated = InventorySubCategory::updateOrCreate(
                ['id' => $subcategory['id']], // Find by ID
                $subcategory // Update with request data
            );

            $subCategoryUpdated = $subCategoryUpdated->fresh();
            $subCategoryUpdated->properties()->detach();
            foreach ($subcategory['properties'] as $property) {
                $subCategoryUpdated->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryCategory $inventoryCategory)
    {
        //
    }

    public function settings()
    {
        return Inertia::render('InventorySetting/Categories', [
            'categories' => InventoryCategory::with(['properties', 'subcategories', 'subcategories.properties'])->paginate(50),
            'properties' => InventoryArticleProperties::all(),
        ]);
    }
}
