<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Project\Http\Resources\ProjectInCalendarResource;
use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CalendarEventResource extends JsonResource
{
    public static $wrap = null;

    private UserCalendarSettings $userCalendarSettings;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $this->userCalendarSettings = Auth::user()->calendar_settings;

        $output = [
            'resource' => class_basename($this),
            'id' => $this->id,
            'start' => $this->start_time->toIso8601String(),
            'startTime' => $this->start_time->toIso8601String(),
            'end' => $this->end_time->toIso8601String(),
            'title' => $this->project?->name ?: $this->eventName ? : $this->event_type->name,
            'alwaysEventName' => $this->eventName,
            'eventName' => $this->eventName,
            'event_type' => $this->event_type,
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
            'created_by' => $this->creator,
            'occupancy_option' => $this->occupancy_option,
            'projectLeaders' => $this->project?->managerUsers,
            'project' => new ProjectInCalendarResource($this->project),
            'collisionCount' => $this->collision_count,
            'is_series' => $this->is_series,
            'series_id' => $this->series_id,
            'option_string' => $this->option_string,
            'series' => $this->series,
            'allDay' => $this->allDay,
            // to display rooms as split
            'split' => $this->room_id,
            // Todo Add Authorization
            'resizable' => true,
            'draggable' => true,
            'canEdit' => Auth::user()->can('update', $this->resource),
            'canAccept' => Auth::user()->can('update', $this->resource),
            'canDelete' => Auth::user()->can('delete', $this->resource),
            'subEvents' => SubEventResource::collection($this->subEvents),
            'comments' => $this->comments,
            'shifts' => $this->shifts,
            'eventTypeColorBackground' => $this->event_type->hex_code . '33',
        ];

        return $this->handleNoUserCalendarWorkShifts($output);
    }

    /**
     * @param array $output
     * @return array<string, mixed>
     */
    private function handleNoUserCalendarWorkShifts(array $output): array
    {
        if (!$this->userCalendarSettings->work_shifts && array_key_exists('shifts', $output)) {
            unset($output['shifts']);
        }
        return $output;
    }
}
