<?php

namespace App\Http\Controllers;

use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Services\PresetShiftService;
use Artwork\Modules\PresetShift\Services\PresetShiftsShiftsQualificationsService;
use Illuminate\Http\Request;

class PresetShiftController extends Controller
{
    public function __construct(
        private readonly PresetShiftService $presetShiftService,
        private readonly PresetShiftsShiftsQualificationsService $presetShiftsShiftsQualificationsService
    ) {
    }

    public function store(int $shiftPreset, Request $request): void
    {
        $presetShiftId = $this->presetShiftService->createFromRequestForShiftPreset($shiftPreset, $request);

        foreach ($request->get('presetShiftsQualifications') as $presetShiftsQualification) {
            $this->presetShiftsShiftsQualificationsService->createShiftsQualificationsForPresetShift(
                $presetShiftId,
                $presetShiftsQualification
            );
        }
    }

    public function update(Request $request, PresetShift $presetShift): void
    {
        $this->presetShiftService->updateFromRequest($presetShift, $request);

        foreach ($request->get('presetShiftsQualifications') as $presetShiftsQualification) {
            $this->presetShiftsShiftsQualificationsService->updateShiftsQualificationForShift(
                $presetShift->id,
                $presetShiftsQualification
            );
        }
    }

    public function destroy(PresetShift $presetShift): void
    {
        $presetShift->delete();
    }
}
