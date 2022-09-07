<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
 */
class EventCalendarDayResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var Carbon $date */
        $date = $this->metaDate;

        $conflictingEvents = $this->sameRoomEvents
            ->filter(fn (Event $event) => $event->id !== $this->id && $event->conflictsWithAny($this->sameRoomEvents));

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'conflicts' => $conflictingEvents->pluck('id'),
            'name' => $this->name,
            'description' => $this->description,
            "start_time" => $this->start_time,
            "start_time_dt_local" => $this->start_time->toDateTimeLocalString(),
            "end_time" => $this->end_time,
            "end_time_dt_local" => $this->end_time->toDateTimeLocalString(),
            "occupancy_option" => $this->occupancy_option,
            "minutes_from_day_start" => $this->getMinutesFromDayStart($date),
            "duration_in_minutes" => $this->start_time->isBefore($date->startOfDay()->subHours(2))
                ? $date->startOfDay()->subHours(2)->diffInMinutes($this->end_time)
                : $this->start_time->diffInMinutes($this->end_time),
            "audience" => $this->audience,
            "is_loud" => $this->is_loud,
            "event_type_id" => $this->event_type_id,
            "event_type" => $this->event_type,
            "room_id" => $this->room_id,
            "user_id" => $this->user_id,
            "project_id" => $this->project_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            'created_by' => $this->creator,
        ];
    }
}
