<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleImageRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleImageRequest;
use Artwork\Modules\Inventory\Models\InventoryArticleImage;

class InventoryArticleImageController extends Controller
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
    public function store(StoreInventoryArticleImageRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryArticleImage $inventoryArticleImage): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryArticleImage $inventoryArticleImage): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateInventoryArticleImageRequest $request,
        InventoryArticleImage $inventoryArticleImage
    ): void {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticleImage $inventoryArticleImage): void
    {
        //
    }
}
