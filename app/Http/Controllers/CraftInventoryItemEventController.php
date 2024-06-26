<?php

namespace App\Http\Controllers;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Illuminate\Http\Request;

class CraftInventoryItemEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CraftInventoryItemEvent $craftInventoryItemEvent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CraftInventoryItemEvent $craftInventoryItemEvent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CraftInventoryItemEvent $craftInventoryItemEvent)
    {
        $craftInventoryItemEvent->update([
            'quantity' => $request->integer('quantity'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CraftInventoryItemEvent $craftInventoryItemEvent)
    {
        $craftInventoryItemEvent->delete();
    }
}
