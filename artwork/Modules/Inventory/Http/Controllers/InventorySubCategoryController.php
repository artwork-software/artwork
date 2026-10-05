<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventorySubCategoryRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventorySubCategoryRequest;
use Artwork\Modules\Inventory\Models\InventorySubCategory;

class InventorySubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventorySubCategoryRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventorySubCategory $inventorySubCategory): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventorySubCategory $inventorySubCategory): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventorySubCategoryRequest $request, InventorySubCategory $inventorySubCategory): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventorySubCategory $inventorySubCategory): void
    {
        $inventorySubCategory->articles()->each(function ($article): void {
            $article->update([
                'inventory_sub_category_id' => null,
            ]);
        });

        $inventorySubCategory->properties()->detach();

        $inventorySubCategory->delete();
    }
}
