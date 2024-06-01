<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarShowEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        /** @todo $destructure this because it contains a lot of implicit queries */
        $resource =  class_basename($this);
        $id = $this->id;
        $start = $this->start_time->utc()->toIso8601String();
            $startTime = $this->start_time;
            $end = $this->end_time->utc()->toIso8601String();
            $title = $this->project?->name ?: $this->eventName ? :$this->getEventType()?->name;
            $alwaysEventName = $this->eventName;
            $eventName = $this->eventName;
            $description = $this->description;
            $audience = $this->audience;
            $isLoud = $this->is_loud;
            $projectId = $this->project_id;
            $projectName = $this->project?->name;
            $roomId = $this->room_id;
            $roomName = $this->room?->name;
            $declinedRoomId = $this->declined_room_id;
            $eventTypeId = $this->event_type_id;
            $eventTypeName = $this->getEventType()?->name;
            $eventTypeAbbreviation = $this->getEventType()?->abbreviation;
            $event_type_color = $this->getEventType()?->hex_code;
            $areaId = $this->room?->area_id;
            $created_at = $this->created_at?->format('d.m.Y, H:i');
            $created_by = $this->creator;
            $occupancy_option = $this->occupancy_option;
            $allDay = $this->allDay;
            $shifts = $this->shifts()->with(['shiftsQualifications'])->get();
            $subEvents = SubEventResource::collection($this->subEvents);
            $eventTypeColorBackground = $this->event_type->hex_code . '33';
            $event_type = $this->event_type;
            $days_of_event = $this->days_of_event;
            $days_of_shifts = $this->days_of_shifts;
            $project = $this->project;
            $option_string = $this->option_string;
            $projectLeaders = $this->project?->managerUsers;
            $is_series = $this->is_series;
            $series = $this->series;

        return [
            'resource' => $resource,
            'id' => $id,
            'start' => $start,
            'startTime' => $startTime,
            'end' => $end,
            'title' => $title,
            'alwaysEventName' => $alwaysEventName,
            'eventName' => $eventName,
            'description' => $description,
            'audience' => $audience,
            'isLoud' => $isLoud,
            'projectId' => $projectId,
            'projectName' => $projectName,
            'roomId' => $roomId,
            'roomName' => $roomName,
            'declinedRoomId' => $declinedRoomId,
            'eventTypeId' => $eventTypeId,
            'eventTypeName' => $eventTypeName,
            'eventTypeAbbreviation' => $eventTypeAbbreviation,
            'event_type_color' => $event_type_color,
            'areaId' => $areaId,
            'created_at' => $created_at,
            'created_by' => $created_by,
            'occupancy_option' => $occupancy_option,
            'allDay' => $allDay,
            'shifts' => $shifts,
            'subEvents' => $subEvents,
            'eventTypeColorBackground' => $eventTypeColorBackground,
            'event_type' => $event_type,
            'days_of_event' => $days_of_event,
            'days_of_shifts' => $days_of_shifts,
            'project' => $project,
            'option_string' => $option_string,
            'projectLeaders' => $projectLeaders,
            'is_series' => $is_series,
            'series' => $series
        ];
    }
}
