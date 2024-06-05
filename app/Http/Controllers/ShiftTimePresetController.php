<?php

namespace App\Http\Controllers;

use Artwork\Modules\ShiftTimePreset\Http\Requests\AddShiftTimePresetRequest;
use Artwork\Modules\ShiftTimePreset\Http\Requests\EditShiftTimePresetRequest;
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
     * Store a newly created resource in storage.
     */
    public function store(AddShiftTimePresetRequest $request): void
    {
        $this->shiftTimePresetService->createByRequest($request->validated());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EditShiftTimePresetRequest $request, ShiftTimePreset $shiftTimePreset): void
    {
        $this->shiftTimePresetService->updateByRequest($shiftTimePreset, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftTimePreset $shiftTimePreset): void
    {
        $this->shiftTimePresetService->delete($shiftTimePreset);
    }
}
