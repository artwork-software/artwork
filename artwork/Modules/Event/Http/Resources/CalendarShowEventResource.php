<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\EventType\Services\EventTypeService;
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
        /** @var EventTypeService $eventTypeService */
        $eventTypeService = app()->get(EventTypeService::class);
        $eventType = $eventTypeService->findById($this->event_type_id);

        $shifts = $this->shifts()->with(['shiftsQualifications'])->first();

        $project = $this->project()->without([
            'shiftRelevantEventTypes',
            'state'
        ])->first();

        $room = $this->room()->without([
            'admins',
            'creator'
        ])->first();

        $creator = $this->creator->without(['calendar_settings', 'shiftCalendarAbo', 'calendarAbo'])->get();

        $resource = class_basename($this);
        $id = $this->id;
        $start = $this->start_time->utc()->toIso8601String();
        $startTime = $this->start_time;
        $end = $this->end_time->utc()->toIso8601String();
        $title = $project?->name ?: $this->eventName ?: $this->getEventType()?->name;
        $alwaysEventName = $this->eventName;
        $eventName = $this->eventName;
        $description = $this->description;
        $audience = $this->audience;
        $isLoud = $this->is_loud;
        $projectId = $this->project_id;
        $projectName = $project?->name;
        $roomId = $this->room_id;
        $roomName = $room?->name;
        $declinedRoomId = $this->declined_room_id;
        $eventTypeId = $this->event_type_id;
        $eventTypeName = $eventType->name;
        $eventTypeAbbreviation = $eventType->abbreviation;
        $event_type_color = $eventType->hex_code;
        $areaId = $room?->area_id;
        $created_at = $this->created_at?->format('d.m.Y, H:i');
        $occupancy_option = $this->occupancy_option;
        $allDay = $this->allDay;
        $subEvents = SubEventResource::collection($this->subEvents);
        $eventTypeColorBackground = $eventType->hex_code . '33';
        $event_type = $eventType;
        $days_of_event = $this->days_of_event;
        $option_string = $this->option_string;
        $projectLeaders = $project?->managerUsers;
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
            'created_by' => $creator,
            'occupancy_option' => $occupancy_option,
            'allDay' => $allDay,
            'shifts' => $shifts,
            'subEvents' => $subEvents,
            'eventTypeColorBackground' => $eventTypeColorBackground,
            'event_type' => $event_type,
            'days_of_event' => $days_of_event,
            'days_of_shifts' => $this->resource->getDaysOfShifts($shifts),
            'project' => $project,
            'option_string' => $option_string,
            'projectLeaders' => $projectLeaders,
            'is_series' => $is_series,
            'series' => $series
        ];
    }
}
