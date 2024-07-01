<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Http\Requests\ItemEvent\DropItemOnInventoryRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemEventServices;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class CraftInventoryItemEventController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftInventoryItemEventServices $craftInventoryItemEventServices,
    ) {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DropItemOnInventoryRequest $request, CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        $this->craftInventoryItemEventServices->updateQuantity(
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

    public function storeMultiple(Request $request): void
    {
        $this->craftInventoryItemEventServices->storeMultiple(
            $request,
            $this->authManager->id()
        );
    }
}
