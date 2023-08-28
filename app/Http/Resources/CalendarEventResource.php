<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \App\Models\Event
 * @property mixed $collision_count
 * @see \App\Builders\EventBuilder::withCollisionCount()
 */
class CalendarEventResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $classString = '';
        if($this->occupancy_option){
            $classString = $this->event_type->svg_name . ' ' . 'occupancy_option_' . $this->event_type->svg_name;
        }else{
            $classString = $this->event_type->svg_name;
        }
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'start' => $this->start_time->utc()->toIso8601String(),
            'startTime' => $this->start_time,
            'end' => $this->end_time->utc()->toIso8601String(),
            'title' => $this->project?->name ?: $this->eventName? : $this->event_type->name,
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
            'eventTypeAbbreviation' =>$this->event_type->abbreviation,
            'class' => $classString,
            'areaId' => $this->room?->area_id,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'created_by' => $this->creator,
            'occupancy_option' => $this->occupancy_option,
            'projectLeaders' => $this->project?->managerUsers,
            'project' => new ProjectInEventResource($this->project),
            'collisionCount'=> $this->collision_count,
            'is_series'=> $this->is_series,
            'series_id'=> $this->series_id,
            'option_string'=>$this->option_string,
            'series' => $this->series()->first(),
            // to display rooms as split
            'split' => $this->room_id,
            // Todo Add Authorization
            'resizable' => true,
            'draggable' => true,

            'canEdit' => Auth::user()->can('update', $this->resource),
            'canAccept' => Auth::user()->can('update', $this->resource),
            'canDelete' => Auth::user()->can('delete', $this->resource),
            'subEvents' => SubEventResource::collection($this->subEvents),
            'comments' => $this->comments()->get(),
            'shifts' => $this->shifts()->get(),

        ];
    }
}
