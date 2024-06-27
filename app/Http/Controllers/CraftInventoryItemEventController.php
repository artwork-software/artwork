<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class CraftInventoryItemEventController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function show(CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CraftInventoryItemEvent $craftInventoryItemEvent): void
    {
        $craftInventoryItemEvent->update([
            'quantity' => $request->integer('quantity'),
        ]);
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
        $events = $request->events;
        foreach ($events as $event) {
            $eventId = $event['id'];
            $eventObject = Event::find($eventId);
            $items = $event['items'];
            foreach ($items as $item) {
                $itemId = $item['id'];
                $count = $item['count'];
                CraftInventoryItemEvent::create([
                    'event_id' => $eventId,
                    'craft_inventory_item_id' => $itemId,
                    'user_id' => $this->authManager->id(),
                    'quantity' => $count,
                    'start' => $eventObject->start_time,
                    'end' => $eventObject->end_time,
                ]);
            }
        }
    }
}
