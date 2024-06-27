<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService
    ) {
    }

    public function inventory(): Response
    {
        return Inertia::render(
            'Inventory/InventoryManagement/Inventory',
            [
                'columns' => $this->craftsInventoryColumnService->getAllOrdered(),
                'crafts' => $this->craftService->getAllWithInventoryCategoriesRelations(),
                'craftFilters' => $this->inventoryManagementUserFilterService
                    ->getFilterOfUser($this->authManager->id())
            ]
        );
    }

    public function scheduling(): Response
    {
        return Inertia::render(
            'Inventory/Scheduling',
            [
                'crafts' => $this->craftService->getAll()
            ]
        );
    }
}
