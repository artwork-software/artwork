<?php

namespace Artwork\Modules\Timeline\Services;

use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Repositories\TimelineRepository;
use Illuminate\Database\Eloquent\Collection;

class TimelineService
{
    public function __construct(private readonly TimelineRepository $timelineRepository)
    {
    }

    public function createFromShiftPresetTimeline(ShiftPresetTimeline $shiftPresetTimeline, int $eventId): Timeline
    {
        $timeline = new Timeline([
            'event_id' => $eventId,
            'start' => $shiftPresetTimeline->start,
            'end' => $shiftPresetTimeline->end,
            'description' => $shiftPresetTimeline->description,
        ]);

        $this->timelineRepository->save($timeline);
        return $timeline;
    }

    public function delete(Timeline $timeline): void
    {
        $this->timelineRepository->delete($timeline);
    }

    public function deleteTimelines(Collection|array $timelines): void
    {
        foreach ($timelines as $timeline) {
            $this->delete($timeline);
        }
    }
}
