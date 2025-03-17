<?php

namespace App\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;

class InventoryArticlePropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
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
    public function update(UpdateInventoryArticlePropertiesRequest $request, InventoryArticleProperties $inventoryArticleProperties)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticleProperties $inventoryArticleProperties)
    {
        //
    }
}
