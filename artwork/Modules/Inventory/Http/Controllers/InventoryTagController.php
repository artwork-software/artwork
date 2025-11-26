<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryTagRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryTagRequest;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\Inventory\Services\InventoryTagManagementService;
use Illuminate\Http\Request;

class InventoryTagController extends Controller
{
    public function __construct(
        private readonly InventoryTagManagementService $tagService,
    ) {
    }

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
    public function store(StoreInventoryTagRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();

        $tag = $this->tagService->createTag(
            $data,
            $data['user_ids'] ?? [],
            $data['department_ids'] ?? []
        );

        return back()->with('success', __('Tag created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryTag $inventoryTag): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryTag $inventoryTag): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateInventoryTagRequest $request,
        InventoryTag $inventoryTag
    ): \Illuminate\Http\RedirectResponse {
        $data = $request->validated();

        $tag = $this->tagService->updateTag(
            $inventoryTag,
            $data,
            $data['user_ids'] ?? [],
            $data['department_ids'] ?? []
        );

        return back()->with('success', __('Tag updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryTag $inventoryTag): void
    {
        $inventoryTag->delete();
    }

    public function reorderTags(InventoryTagGroup $group, Request $request)
    {
        $data = $request->validate([
            'ordered_ids'   => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:inventory_tags,id'],
        ]);

        $this->tagService->reorderTags($group, $data['ordered_ids']);
    }
}
