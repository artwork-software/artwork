<?php

namespace App\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Inertia\Inertia;

class InventoryArticlePropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('InventorySetting/Properties', [
            'properties' => InventoryArticleProperties::paginate(50),
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
    public function store(StoreInventoryArticlePropertiesRequest $request)
    {
        InventoryArticleProperties::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryArticleProperties $inventoryArticleProperties)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryArticleProperties $inventoryArticleProperties)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryArticlePropertiesRequest $request, InventoryArticleProperties $inventoryArticleProperty)
    {
        $inventoryArticleProperty->update($request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticleProperties $inventoryArticleProperty)
    {
        $inventoryArticleProperty->delete();
    }
}
