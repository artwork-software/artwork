<?php

namespace Artwork\Modules\ShiftPresetTimeline\Services;

use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Artwork\Modules\ShiftPresetTimeline\Repositories\ShiftPresetTimelineRepository;
use Artwork\Modules\Timeline\Models\Timeline;

readonly class ShiftPresetTimelineService
{
    public function __construct(private ShiftPresetTimelineRepository $shiftPresetTimelineRepository)
    {
    }

    public function createFromExistingTimeline(int $shiftPresetId, Timeline $timeline): ShiftPresetTimeline
    {
        $shiftPresetTimeline = new ShiftPresetTimeline([
            'shift_preset_id' => $shiftPresetId,
            'start' => $timeline->start,
            'end' => $timeline->end,
            'description' => $timeline->description,
        ]);
        $this->shiftPresetTimelineRepository->save($shiftPresetTimeline);

        return $shiftPresetTimeline;
    }

    public function createFromExistingPresetTimeline(
        int $shiftPresetId,
        ShiftPresetTimeline $shiftPresetTimeline
    ): ShiftPresetTimeline {
        $duplicatedShiftPresetTimeline = new ShiftPresetTimeline([
            'shift_preset_id' => $shiftPresetId,
            'start' => $shiftPresetTimeline->start,
            'end' => $shiftPresetTimeline->end,
            'description' => $shiftPresetTimeline->description,
        ]);
        $this->shiftPresetTimelineRepository->save($duplicatedShiftPresetTimeline);

        return $duplicatedShiftPresetTimeline;
    }
}
