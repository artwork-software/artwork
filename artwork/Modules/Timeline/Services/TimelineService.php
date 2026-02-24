<?php

namespace Artwork\Modules\Timeline\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\Timeline\Http\Requests\UpdateTimelineRequest;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Repositories\TimelineRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class TimelineService
{
    public function __construct(private TimelineRepository $timelineRepository)
    {
    }

    public function createFromShiftPresetTimeline(ShiftPresetTimeline $shiftPresetTimeline, Event $event): Timeline
    {
        $timeline = new Timeline([
            'event_id' => $event->id,
            'start_date' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'end_date' => Carbon::parse($event->end_time)->format('Y-m-d'),
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

    public function updateTimeline(Timeline $timeline, SupportCollection $data): Timeline
    {

        $startDate = Carbon::parse($data->get('start_date') . ' ' . $data->get('start'));
        $endDate = Carbon::parse($data->get('end_date') . ' ' . $data->get('end'));

        [$startTimeConverted, $endTimeConverted] = $this->processTimes(
            $startDate,
            $data->get('start'),
            $data->get('end'),
            Carbon::parse($data->get('end_date'))
        );

        $timeline->update([
            'description' => $data->get('description'),
            'start' => $startTimeConverted->format('H:i'),
            'end' => $endTimeConverted->format('H:i'),
            'start_date' => $startTimeConverted->format('Y-m-d'),
            'end_date' => $endTimeConverted->format('Y-m-d'),
        ]);

        return $timeline;
    }

    /**
     * @param Carbon $day
     * @param string|null $startTime
     * @param string|null $endTime
     * @param Carbon|null $endDate
     * @return array{Carbon, Carbon, bool}
     */
    private function processTimes(Carbon $startDate, ?string $startTime, ?string $endTime, ?Carbon $endDate): array
    {
        $endDay = clone $startDate;
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        if ($endDate && !$endDate->isSameDay($startDate)) {
            $endDay = clone $endDate;
        } elseif ($endTime->lte($startTime)) {
            $endDay->addDay();
        }
        $startDate->setTimeFromTimeString($startTime->toTimeString());
        $endDay->setTimeFromTimeString($endTime->toTimeString());
        return [$startDate, $endDay];
    }
}
