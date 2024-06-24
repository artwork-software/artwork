<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\CreateCraftInventoryGroupRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Item\CreateCraftInventoryItemRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
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
                ->with('error', 'Gegenstand konnte nicht gespeichert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
