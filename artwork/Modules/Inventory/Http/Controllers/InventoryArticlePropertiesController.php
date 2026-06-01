<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticlePropertiesRequest;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryArticlePropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('InventorySetting/Properties', [
            'properties' => InventoryArticleProperties::ordered()->paginate(50),
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

    /**
     * Persist a new global property order (Ref 1.41). Expects an ordered array of
     * property ids and the absolute start offset of the current page so the order
     * stays globally consistent across paginated pages.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:inventory_article_properties,id'],
            'start' => ['nullable', 'integer', 'min:0'],
        ]);

        $start = (int) ($validated['start'] ?? 0);
        foreach ($validated['ids'] as $index => $id) {
            InventoryArticleProperties::where('id', $id)->update(['order' => $start + $index]);
        }

        return redirect()->back()->with('success', 'Property order updated successfully.');
    }
}
