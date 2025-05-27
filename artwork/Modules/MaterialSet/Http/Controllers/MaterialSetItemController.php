<?php

namespace Artwork\Modules\MaterialSet\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\MaterialSet\Models\MaterialSetItem;
use Artwork\Modules\MaterialSet\Http\Requests\StoreMaterialSetItemRequest;
use Artwork\Modules\MaterialSet\Http\Requests\UpdateMaterialSetItemRequest;

class MaterialSetItemController extends Controller
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
    public function store(StoreMaterialSetItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MaterialSetItem $materialSetItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaterialSetItem $materialSetItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaterialSetItemRequest $request, MaterialSetItem $materialSetItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialSetItem $materialSetItem)
    {
        //
    }
}
