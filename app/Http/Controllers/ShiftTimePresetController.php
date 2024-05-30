<?php

namespace App\Http\Controllers;

use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;
use Artwork\Modules\ShiftTimePreset\Services\ShiftTimePresetService;
use Illuminate\Http\Request;

class ShiftTimePresetController extends Controller
{
    public function __construct(
        private readonly ShiftTimePresetService $shiftTimePresetService
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
        $this->shiftTimePresetService->createByRequest($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(ShiftTimePreset $shiftTimePreset): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftTimePreset $shiftTimePreset): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShiftTimePreset $shiftTimePreset): void
    {
        $this->shiftTimePresetService->updateByRequest($shiftTimePreset, $request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftTimePreset $shiftTimePreset): void
    {
        $this->shiftTimePresetService->delete($shiftTimePreset);
    }
}
