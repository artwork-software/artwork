<?php

namespace Artwork\Modules\InventorySetting\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\EventType\Services\EventTypeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventorySettingsController extends Controller
{

    public function __construct(
        private readonly EventTypeService $eventTypeService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('InventorySetting/Index', [
            'eventTypes' => $this->eventTypeService->getAll()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): void
    {
        //
    }
}
