<?php

namespace App\Http\Resources;

use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarShowEventInShiftPlan extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $shifts =  $this->shifts()->with(['shiftsQualifications'])->get();
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'start' => $this->start_time->utc()->toIso8601String(),
            'startTime' => $this->start_time,
            'end' => $this->end_time->utc()->toIso8601String(),
            'eventName' => $this->eventName,
            'description' => $this->description,
            'audience' => $this->audience,
            'isLoud' => $this->is_loud,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->name,
            'roomId' => $this->room_id,
            'roomName' => $this->room?->name,
            'declinedRoomId' => $this->declined_room_id,
            'eventTypeId' => $this->event_type_id,
            'eventTypeName' => $this->event_type->name,
            'eventTypeAbbreviation' => $this->event_type->abbreviation,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'created_by' => $this->creator,
            'occupancy_option' => $this->occupancy_option,
            'allDay' => $this->allDay,
            'shifts' => $shifts,
            'subEvents' => SubEventResource::collection($this->subEvents),
            'event_type' => $this->event_type,
            'days_of_event' => $this->days_of_event,
            'days_of_shifts' => $this->resource->getDaysOfShifts($shifts),
            'project' => $this->project,
            'option_string' => $this->option_string,
            'formatted_dates' => $this->formatted_dates,
            'timesWithoutDates' => $this->timesWithoutDates,
        ];
    }
}
