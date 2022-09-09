<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
 */
class EventForProjectResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'start_time_dt_local' => $this->start_time->toDateTimeLocalString(),
            'end_time' => $this->end_time,
            'end_time_dt_local' => $this->end_time->toDateTimeLocalString(),
            'occupancy_option' => $this->occupancy_option,
            'audience' => $this->audience,
            'is_loud' => $this->is_loud,
            'event_type_id' => $this->event_type_id,
            'room_id' => $this->room_id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->creator,
        ];
    }
}
