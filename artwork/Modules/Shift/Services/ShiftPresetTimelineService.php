<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\Shift\Repositories\ShiftPresetTimelineRepository;
use Artwork\Modules\Timeline\Models\Timeline;
use Illuminate\Support\Facades\DB;

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

    public function duplicate(ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $duplicatedShiftPresetTimeline = $shiftPresetTimeline->replicate();
        $duplicatedShiftPresetTimeline->name = $shiftPresetTimeline->name . ' (copy)';
        $duplicatedShiftPresetTimeline->save();

        $shiftPresetTimeline->times->each(function ($time) use ($duplicatedShiftPresetTimeline): void {
            $duplicatedShiftPresetTimeline->times()->create([
                'start' => $time->start,
                'end' => $time->end,
                'description' => $time->description
            ]);
        });
    }

    /**
     * Update the timeline preset and associated times.
     *
     * @param ShiftPresetTimeline $shiftPresetTimeline
     * @param array $data
     * @return ShiftPresetTimeline
     */
    public function updateTimelinePreset(ShiftPresetTimeline $shiftPresetTimeline, array $data): ShiftPresetTimeline
    {
        // Atomar: scheitert das Anlegen der neuen Zeiten, bleiben die alten erhalten
        DB::transaction(function () use ($shiftPresetTimeline, $data): void {
            $shiftPresetTimeline->update([
                'name' => $data['name'],
            ]);

            $shiftPresetTimeline->times()->each(function ($time): void {
                $time->delete();
            });

            $this->attachTimes($shiftPresetTimeline, $data['dataset']);
        });

        return $shiftPresetTimeline;
    }

    /**
     * Store a new timeline preset and associated times.
     *
     * @param array $data
     * @return ShiftPresetTimeline
     */
    public function storeTimelinePreset(array $data): ShiftPresetTimeline
    {
        $timelinePreset = ShiftPresetTimeline::create([
            'name' => $data['name'],
        ]);

        $this->attachTimes($timelinePreset, $data['dataset']);

        return $timelinePreset;
    }

    /**
     * Attach times to a timeline preset.
     *
     * @param ShiftPresetTimeline $timelinePreset
     * @param array $times
     * @return void
     */
    private function attachTimes(ShiftPresetTimeline $timelinePreset, array $times): void
    {
        foreach ($times as $time) {
            // Schlüssel sind optional (z. B. "ab 18:00" ohne Ende oder ohne Beschreibung)
            $timelinePreset->times()->create([
                'start' => $time['start'] ?? null,
                'end' => $time['end'] ?? null,
                'description' => $time['description'] ?? null,
            ]);
        }
    }
}
