<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Services\PresetShiftService;
use Artwork\Modules\Shift\Services\PresetShiftsShiftsQualificationsService;
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
        $request->validate(['description' => ['nullable', 'string', 'max:10000']]);

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
        // Ohne Prüfung führten ungültige Zeiten, negative Pausen/Bedarfe oder eine fehlende
        // Qualifikationsliste zu SQL-Fehlern bzw. einem foreach über null (500)
        $validated = $request->validate([
            'start' => ['sometimes', 'required', 'date_format:H:i,H:i:s'],
            'end' => ['sometimes', 'required', 'date_format:H:i,H:i:s'],
            'break_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:1440'],
            'craft_id' => ['sometimes', 'required', 'integer', 'exists:crafts,id'],
            'description' => ['nullable', 'string', 'max:10000'],
            'presetShiftsQualifications' => ['sometimes', 'array'],
            'presetShiftsQualifications.*.shift_qualification_id' => [
                'required',
                'integer',
                'exists:shift_qualifications,id',
            ],
            'presetShiftsQualifications.*.value' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $this->presetShiftService->updateFromRequest($presetShift, $request);

        foreach ($validated['presetShiftsQualifications'] ?? [] as $presetShiftsQualification) {
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
