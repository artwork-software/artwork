<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Services\CraftService;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private readonly CraftService $craftService)
    {
    }

    public function inventory(): Response
    {
        return Inertia::render(
            'Inventory/Inventory',
            [
                'crafts' => $this->craftService->getAll()
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
