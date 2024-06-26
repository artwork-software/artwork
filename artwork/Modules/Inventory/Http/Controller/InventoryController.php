<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService
    ) {
    }

    public function inventory(): Response
    {
        throw new UnauthorizedHttpException('Unauthorized');
        return Inertia::render(
            'Inventory/Inventory',
            [
                'columns' => $this->craftsInventoryColumnService->getAllOrdered(),
                'crafts' => $this->craftService->getAll(
                    [
                        'inventoryCategories',
                        'inventoryCategories.groups',
                        'inventoryCategories.groups.items',
                        'inventoryCategories.groups.items.cells',
                        'inventoryCategories.groups.items.cells.column',
                    ]
                )
            ]
        );
    }

    public function scheduling(): Response
    {
        throw new UnauthorizedHttpException('Unauthorized');
        return Inertia::render(
            'Inventory/Scheduling',
            [
                'crafts' => $this->craftService->getAll()
            ]
        );
    }
}
