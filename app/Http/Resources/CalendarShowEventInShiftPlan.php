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
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'start' => $this->start_time->utc()->toIso8601String(),
            'startTime' => $this->start_time,
            'end' => $this->end_time->utc()->toIso8601String(),
            'title' => $this->project?->name ?: $this->eventName ? : $this->event_type->name,
            'alwaysEventName' => $this->eventName,
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
            'event_type_color' => $this->event_type->hex_code,
            'areaId' => $this->room?->area_id,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            //'created_by' => $this->creator,
            'occupancy_option' => $this->occupancy_option,
            'allDay' => $this->allDay,
            'shifts' => $this->shifts()->with(['shiftsQualifications'])->get(),
            'subEvents' => SubEventResource::collection($this->subEvents),
            'event_type' => $this->event_type,
            'days_of_event' => $this->days_of_event,
            'days_of_shifts' => $this->days_of_shifts,
            'project' => $this->project,
            'option_string' => $this->option_string,
            'is_series' => $this->is_series,
            'series' => $this->series,
            'formatted_dates' => $this->formatted_dates,
            'timesWithoutDates' => $this->timesWithoutDates,
        ];
    }
}
