<?php

namespace App\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class MinimalShiftPlanEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $eventType = $this->getAttribute('event_type');
        $startTime = $this->getAttribute('start_time');

        return [
            'id' => $this->getAttribute('id'),
            'start' => $startTime->utc()->toIso8601String(),
            'startTime' => $startTime,
            'end' => $this->getAttribute('end_time')->utc()->toIso8601String(),
            'eventName' => $this->getAttribute('eventName'),
            'description' => $this->getAttribute('description'),
            'audience' => $this->getAttribute('audience'),
            'isLoud' => $this->getAttribute('is_loud'),
            'projectId' => $this->getAttribute('project_id'),
            'projectName' => $this->getAttribute('project')?->getAttribute('name'),
            'roomId' => $this->getAttribute('room_id'),
            'roomName' => $this->getAttribute('room')?->getAttribute('name'),
            'declinedRoomId' => $this->getAttribute('declined_room_id'),
            'eventTypeId' => $this->getAttribute('event_type_id'),
            'eventTypeName' => $eventType->getAttribute('name'),
            'eventTypeAbbreviation' => $eventType->getAttribute('abbreviation'),
            'eventTypeColor' => $eventType->getAttribute('hex_code'),
            'created_at' => $this->getAttribute('created_at')->format('d.m.Y, H:i'),
            'occupancy_option' => $this->getAttribute('occupancy_option'),
            'allDay' => $this->getAttribute('allDay'),
            'shifts' => MinimalShiftPlanShiftResource::collection($this->getAttribute('shifts'))->resolve(),
            'days_of_event' => $this->getAttribute('days_of_event'),
            'days_of_shifts' => $this->getDaysOfShifts($this->getAttribute('shifts')),
            'option_string' => $this->getAttribute('option_string'),
            'formatted_dates' => $this->getAttribute('formatted_dates'),
            'timesWithoutDates' => $this->getAttribute('timesWithoutDates'),
            'is_series' => $this->getAttribute('is_series'),
        ];
    }
}
