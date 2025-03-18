<?php

namespace App\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryCategoryRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryCategory $inventoryCategory): \Inertia\Response
    {
        $inventoryCategory->load(['subcategories', 'subcategories.properties', 'articles', 'properties']);
        return Inertia::render('Inventory/Index', [
            'categories' => InventoryCategory::with(['subcategories', 'properties'])->get(),
            'currentCategory' => $inventoryCategory,
            'articles' => $inventoryCategory->articles()->paginate(50),
            'articlesCount' => InventoryArticle::count(),
        ]);
    }

    public function showSubCategory(InventoryCategory $inventoryCategory, InventorySubCategory $subCategory): \Inertia\Response
    {
        $inventoryCategory->load(['subcategories', 'properties', 'articles']);
        $subCategory->load(['properties', 'articles']);
        return Inertia::render('Inventory/Index', [
            'categories' => InventoryCategory::with('subcategories')->get(),
            'currentCategory' => $inventoryCategory,
            'currentSubCategory' => $subCategory,
            'articles' => $subCategory->articles()->paginate(50),
            'articlesCount' => InventoryArticle::count(),
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
        //
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
        ]);
    }
}
