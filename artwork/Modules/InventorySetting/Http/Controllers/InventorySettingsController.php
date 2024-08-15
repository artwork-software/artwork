<?php

namespace Artwork\Modules\InventorySetting\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\EventType\Services\EventTypeService;
use Inertia\Inertia;
use Inertia\Response;

class InventorySettingsController extends Controller
{

    public function __construct(
        private readonly EventTypeService $eventTypeService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('InventorySetting/Index', [
            'eventTypes' => $this->eventTypeService->getAll()
        ]);
    }
}
