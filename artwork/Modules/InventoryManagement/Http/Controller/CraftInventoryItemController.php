<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Item\CreateCraftInventoryItemRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Item\UpdateCraftInventoryItemOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryItemController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryItemService $craftInventoryItemService
    ) {
    }

    public function create(CreateCraftInventoryItemRequest $request): RedirectResponse
    {
        try {
            $this->craftInventoryItemService->create(
                $request->integer('groupId'),
                $request->integer('order'),
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not create crafts inventory item for reason: "%s"',
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', __('flash-messages.inventory-management.item.errors.create'));
        }

        return $this->redirector->back();
    }

    public function updateOrder(
        CraftInventoryItem $craftInventoryItem,
        UpdateCraftInventoryItemOrderRequest $request
    ) {
        $order = $request->integer('order');

        try {
            $this->craftInventoryItemService->updateOrder($craftInventoryItem, $order);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory item order to: "%s" for reason: "%s"',
                    $order,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', __('flash-messages.inventory-management.item.errors.updateOrder'));
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftInventoryItem $craftInventoryItem): RedirectResponse
    {
        if (!$this->craftInventoryItemService->forceDelete($craftInventoryItem)) {
            return $this->redirector
                ->back()
                ->with('error', __('flash-messages.inventory-management.item.errors.delete'));
        }

        return $this->redirector->back();
    }
}
