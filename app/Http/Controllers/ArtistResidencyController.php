<?php

namespace App\Http\Controllers;

use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Illuminate\Http\Request;

class ArtistResidencyController extends Controller
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
    public function store(Request $request): void
    {
        ArtistResidency::create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(ArtistResidency $artistResidency): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ArtistResidency $artistResidency): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArtistResidency $artistResidency): void
    {
        $artistResidency->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArtistResidency $artistResidency): void
    {
        $artistResidency->delete();
    }

    public function duplicate(ArtistResidency $artistResidency): void
    {
        $artistResidency->replicate()->save();
    }
}
