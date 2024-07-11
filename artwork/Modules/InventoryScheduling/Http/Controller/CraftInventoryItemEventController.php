<?php

namespace Artwork\Modules\InventoryScheduling\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
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

    public function dropItemToEvent(
        DropItemOnInventoryRequest $request,
        CraftInventoryItem $item,
        Event $event
    ): void {
        $this->craftInventoryItemEventService->dropItemToEvent(
            $item,
            $event,
            $this->authManager->id(),
            $request->integer('quantity')
        );
    }
}
