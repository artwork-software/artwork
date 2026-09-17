<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisclosureComponentsRequest;
use App\Http\Requests\UpdateDisclosureComponentsRequest;
use Artwork\Modules\Project\Models\DisclosureComponents;

class DisclosureComponentsController extends Controller
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
    public function store(StoreDisclosureComponentsRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DisclosureComponents $disclosureComponents): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DisclosureComponents $disclosureComponents): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDisclosureComponentsRequest $request, DisclosureComponents $disclosureComponents): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DisclosureComponents $disclosureComponents): void
    {
        //
    }
}
