<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroupFolder;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupFolderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryGroupFolderController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryGroupFolderService $craftInventoryGroupService,
        private readonly Translator $translator
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
    public function create(Request $request)
    {
        try {
            $this->craftInventoryGroupService->create(
                $request->integer('groupId'),
                $request->string('name'),
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not create crafts inventory group for reason: "%s"',
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', $this->translator->get('flash-messages.inventory-management.group.errors.create'));
        }

        return $this->redirector->back();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CraftInventoryGroupFolder $craftInventoryGroupFolder): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CraftInventoryGroupFolder $craftInventoryGroupFolder): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CraftInventoryGroupFolder $craftInventoryGroupFolder): void
    {
        $craftInventoryGroupFolder->update([
            'name' => $request->string('name')
        ]);
    }

    public function updateOrder(Request $request): \Illuminate\Http\RedirectResponse
    {
        foreach ($request->collect('folders') as $folder) {
            CraftInventoryGroupFolder::findOrFail($folder['id'])->update(['order' => $folder['order']]);
        }

        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CraftInventoryGroupFolder $craftInventoryGroupFolder): void
    {
        $craftInventoryGroupFolder->items()->each(function ($item) use ($craftInventoryGroupFolder): void {
            $item->update([
                'craft_inventory_group_folder_id' => null,
                'craft_inventory_group_id' => $craftInventoryGroupFolder->craft_inventory_group_id
            ]);
        });

        $craftInventoryGroupFolder->delete();
    }
}
