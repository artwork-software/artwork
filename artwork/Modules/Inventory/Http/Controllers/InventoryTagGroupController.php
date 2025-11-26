<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryTagGroupRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryTagGroupRequest;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\Inventory\Services\InventoryTagManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryTagGroupController extends Controller
{
    public function __construct(
        private readonly InventoryTagManagementService $tagService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('InventorySetting/TagGroupIndex', [
            'tagGroups' => InventoryTagGroup::with('tags')->orderBy('position')->get(),
            'tags'      => InventoryTag::with(['allowedUsers', 'allowedDepartments'])->orderBy('position')->get(),
        ]);
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
    public function store(StoreInventoryTagGroupRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->tagService->createGroup($request->validated());

        return back()->with('success', __('Tag group created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryTagGroup $inventoryTagGroup): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryTagGroup $inventoryTagGroup): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateInventoryTagGroupRequest $request,
        InventoryTagGroup $inventoryTagGroup
    ): \Illuminate\Http\RedirectResponse {
        $this->tagService->updateGroup($inventoryTagGroup, $request->validated());

        return back()->with('success', __('Tag group updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryTagGroup $inventoryTagGroup): void
    {
        $inventoryTagGroup->delete();
    }

    public function reorderGroups(Request $request): void
    {
        $data = $request->validate([
            'ordered_ids'   => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:inventory_tag_groups,id'],
        ]);

        $this->tagService->reorderGroups($data['ordered_ids']);
    }
}
