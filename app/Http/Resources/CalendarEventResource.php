<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'resource' => class_basename($this),
            'id' => $this->id,

            'start' => $this->start_time->utc()->toIso8601String(),
            'end' => $this->end_time->utc()->toIso8601String(),
            'title' => $this->name,
            'description' => $this->description,
            'audience' => $this->audience,
            'isLoud' => $this->is_loud,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->name,
            'roomId' => $this->room_id,
            'eventTypeId' => $this->event_type_id,
            'areaId' => $this->room?->area_id,
            'created_at' => $this->created_at->format('d.m.Y, H:i'),
            'created_by' => $this->creator,
            'occupancy_option' => $this->occupancy_option,

            'collisionCount'=> $this->collision_count,

            // to display rooms as split
            'split' => $this->room_id,

            // Todo Add Authorization
            'resizable' => true,
            'draggable' => true,
        ];
    }
}
