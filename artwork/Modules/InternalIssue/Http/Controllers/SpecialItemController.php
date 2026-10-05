<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InternalIssue\Http\Requests\StoreSpecialItemRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateSpecialItemRequest;
use Artwork\Modules\InternalIssue\Models\SpecialItem;

class SpecialItemController extends Controller
{
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
    public function store(StoreSpecialItemRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SpecialItem $specialItem): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SpecialItem $specialItem): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpecialItemRequest $request, SpecialItem $specialItem): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SpecialItem $specialItem): void
    {
        //
    }
}
