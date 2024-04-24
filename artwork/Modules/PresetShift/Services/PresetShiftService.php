<?php

namespace Artwork\Modules\PresetShift\Services;

use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Repositories\PresetShiftRepository;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Http\Request;

readonly class PresetShiftService
{
    public function __construct(private PresetShiftRepository $presetShiftRepository)
    {
    }

    public function createFromRequestForShiftPreset(int $shiftPresetId, Request $request): int
    {
        $presetShift = $this->presetShiftRepository->save(
            new PresetShift(
                [
                    'shift_preset_id' => $shiftPresetId,
                    'start' => $request->get('start'),
                    'end' => $request->get('end'),
                    'break_minutes' => $request->get('break_minutes'),
                    'craft_id' => $request->get('craft_id'),
                    'description' => $request->get('description')
                ]
            )
        );

        return $presetShift->id;
    }

    public function updateFromRequest(PresetShift $presetShift, Request $request): void
    {
        $presetShift->update(
            $request->only(
                [
                    'start',
                    'end',
                    'break_minutes',
                    'craft_id',
                    'description'
                ]
            )
        );
    }

    public function createPresetShiftFromExistingShift(int $shiftPresetId, Shift $shift): PresetShift
    {
        $presetShift = new PresetShift([
            'shift_preset_id' => $shiftPresetId,
            'start' => $shift->start,
            'end' => $shift->end,
            'break_minutes' => $shift->break_minutes,
            'craft_id' => $shift->craft_id,
            'description' => $shift->description
        ]);
        $this->presetShiftRepository->save($presetShift);

        return $presetShift;
    }

    public function createPresetShiftFromExistingPresetShift(int $shiftPresetId, PresetShift $presetShift): PresetShift
    {
        $duplicatedPresetShift = new PresetShift([
            'shift_preset_id' => $shiftPresetId,
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description
        ]);
        $this->presetShiftRepository->save($duplicatedPresetShift);

        return $duplicatedPresetShift;
    }
}
