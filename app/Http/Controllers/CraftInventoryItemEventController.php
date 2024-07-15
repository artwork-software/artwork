<?php

namespace App\Http\Controllers;

use Artwork\Modules\InventoryScheduling\Http\Requests\DropItemOnInventoryRequest;
use Artwork\Modules\InventoryScheduling\Http\Requests\StoreMultipleInventoryItemsInEvent;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Illuminate\Auth\AuthManager;

class CraftInventoryItemEventController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService,
    ) {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DropItemOnInventoryRequest $request, CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        $this->craftInventoryItemEventService->updateQuantity(
            $request->integer('quantity'),
            $craftInventoryItemEvent
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        $craftInventoryItemEvent->delete();
    }

    public function storeMultiple(StoreMultipleInventoryItemsInEvent $request): void
    {
        $this->craftInventoryItemEventService->storeMultiple(
            $request->collect('events'),
            $this->authManager->id()
        );
    }
}
