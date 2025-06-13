<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetTimelineTime;
use Illuminate\Http\Request;

class PresetTimelineTimeController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PresetTimelineTime $presetTimelineTime): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PresetTimelineTime $presetTimelineTime): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PresetTimelineTime $presetTimelineTime): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PresetTimelineTime $presetTimelineTime): void
    {
        $presetTimelineTime->delete();
    }
}
