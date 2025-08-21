<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleStatusRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleStatusRequest;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Inertia\Inertia;

class InventoryArticleStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('InventorySetting/ArticleStatusSettings', [
            'statuses' => InventoryArticleStatus::all(),
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
    public function store(StoreInventoryArticleStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryArticleStatus $inventoryArticleStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryArticleStatus $inventoryArticleStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryArticleStatusRequest $request, InventoryArticleStatus $inventoryArticleStatus)
    {
        $inventoryArticleStatus->update($request->validated());
        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticleStatus $inventoryArticleStatus)
    {
        //
    }
}
