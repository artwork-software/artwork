<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function inventory(): Response
    {
        return Inertia::render('Inventory/Inventory');
    }

    public function scheduling(): Response
    {
        return Inertia::render('Inventory/Scheduling');
    }
}
