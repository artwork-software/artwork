<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;

readonly class EventTimelineService
{
    public function __construct(
        private EventService $eventService
    ) {
    }

    /**
     * Update timelines for an event.
     *
     * @param Event $event
     * @param array $dataset
     * @return void
     */
    public function updateTimeLines(Event $event, array $dataset): void
    {
        $event->timelines()->delete();

        foreach ($dataset as $timeline) {
            $this->createTimeline($event, $timeline);
        }
    }

    /**
     * Add timelines to an event.
     *
     * @param Event $event
     * @param array $dataset
     * @return void
     */
    public function addTimeLines(Event $event, array $dataset): void
    {
        foreach ($dataset as $timeline) {
            $this->createTimeline($event, $timeline);
        }
    }

    /**
     * Import timelines from a timeline preset.
     *
     * @param Event $event
     * @param ShiftPresetTimeline $shiftPresetTimeline
     * @return void
     */
    public function importTimelinePreset(Event $event, ShiftPresetTimeline $shiftPresetTimeline): void
    {
        foreach ($shiftPresetTimeline->times as $timeline) {
            $this->createTimeline($event, [
                'start' => $timeline->start,
                'end' => $timeline->end,
                'description' => $timeline->description,
            ]);
        }
    }

    /**
     * Store a timeline preset from an event's timelines.
     *
     * @param Event $event
     * @param string $name
     * @return ShiftPresetTimeline
     */
    public function storeTimelinePresetFromEvent(Event $event, string $name): ShiftPresetTimeline
    {
        $preset = ShiftPresetTimeline::create(['name' => $name]);

        $preset->times()->createMany(
            $event->timelines->map(function ($timeline) {
                return [
                    'start' => $timeline->start,
                    'end' => $timeline->end,
                    'description' => $timeline->description,
                ];
            })->toArray()
        );

        return $preset;
    }

    /**
     * Helper method to create a timeline entry for an event.
     *
     * @param Event $event
     * @param array $timeline
     * @return void
     */
    private function createTimeline(Event $event, array $timeline): void
    {
        [$startTime, $endTime, $allDay] = $this->eventService->processEventTimesForTimeline(
            Carbon::parse($event->start_time),
            $timeline['start'] ?? null,
            $timeline['end'] ?? null
        );

        $event->timelines()->create([
            'start_date' => Carbon::parse($startTime)->format('Y-m-d'),
            'end_date' => Carbon::parse($endTime)->format('Y-m-d'),
            'start' => Carbon::parse($startTime)->format('H:i:s'),
            'end' => Carbon::parse($endTime)->format('H:i:s'),
            'description' => $timeline['description'],
        ]);
    }
}
