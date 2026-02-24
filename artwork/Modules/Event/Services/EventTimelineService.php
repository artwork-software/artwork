<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Timeline\Models\Timeline;
use Carbon\Carbon;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Illuminate\Support\Facades\DB;

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
        DB::transaction(function () use ($event, $dataset): void {
            $event->timelines()->delete();

            $rows = $this->buildTimelineRows($event, $dataset);
            if ($rows !== []) {
                Timeline::query()->insert($rows);
            }
        });
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
        $rows = $this->buildTimelineRows($event, $dataset);
        if ($rows !== []) {
            Timeline::query()->insert($rows);
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
        $rows = $this->buildTimelineRows(
            $event,
            $shiftPresetTimeline->times
                ->map(fn ($timeline) => [
                    'start' => $timeline->start,
                    'end' => $timeline->end,
                    'description' => $timeline->description,
                ])
                ->toArray()
        );

        if ($rows !== []) {
            Timeline::query()->insert($rows);
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
    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTimelineRows(Event $event, array $dataset): array
    {
        if ($dataset === []) {
            return [];
        }

        $baseDay = Carbon::parse($event->start_time);
        $now = now();

        $rows = [];
        foreach ($dataset as $timeline) {
            // `processEventTimesForTimeline` mutiert das Carbon-Objekt, daher pro Row klonen
            [$startTime, $endTime] = $this->eventService->processEventTimesForTimeline(
                $baseDay->copy(),
                $timeline['start'] ?? null,
                $timeline['end'] ?? null
            );

            $rows[] = [
                'event_id' => $event->id,
                'start_date' => Carbon::parse($startTime)->format('Y-m-d'),
                'end_date' => Carbon::parse($endTime)->format('Y-m-d'),
                'start' => Carbon::parse($startTime)->format('H:i:s'),
                'end' => Carbon::parse($endTime)->format('H:i:s'),
                'description' => $timeline['description'] ?? null,
                'start_or_end' => (bool) ($timeline['start'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
