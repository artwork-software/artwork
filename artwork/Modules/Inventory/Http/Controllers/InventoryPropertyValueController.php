<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryPropertyValueRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryPropertyValueRequest;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;

class InventoryPropertyValueController extends Controller
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
    public function store(StoreInventoryPropertyValueRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryPropertyValue $inventoryPropertyValue): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryPropertyValue $inventoryPropertyValue): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateInventoryPropertyValueRequest $request,
        InventoryPropertyValue $inventoryPropertyValue
    ): void {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryPropertyValue $inventoryPropertyValue): void
    {
        //
    }
}
