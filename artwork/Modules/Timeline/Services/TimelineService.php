<?php

namespace Artwork\Modules\Timeline\Services;

use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Repositories\TimelineRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class TimelineService
{
    public function __construct(private TimelineRepository $timelineRepository)
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

    public function delete(Timeline $timeline): bool
    {
        return $this->timelineRepository->delete($timeline);
    }

    public function deleteTimelines(Collection|array $timelines): void
    {
        /** @var Timeline $timeline */
        foreach ($timelines as $timeline) {
            $this->delete($timeline);
        }
    }

    public function restoreTimelines(Collection|array $timelines): void
    {
        /** @var Timeline $timeline */
        foreach ($timelines as $timeline) {
            $this->restore($timeline);
        }
    }

    public function forceDelete(Timeline $timeline): bool
    {
        return $this->timelineRepository->forceDelete($timeline);
    }

    public function forceDeleteTimelines(Collection|array $timelines): void
    {
        /** @var Timeline $timeline */
        foreach ($timelines as $timeline) {
            $this->forceDelete($timeline);
        }
    }

    public function restore(Timeline $timeline): void
    {
        $this->timelineRepository->restore($timeline);
    }
}
