<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
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
            'roomId' => $this->room_id,
            'start' => $this->start_time->toDateTimeString(),
            'end' => $this->end_time->toDateTimeString(),
            'title' => $this->name,

            // vue infos
            'class' => 'leisure',

            // calendar edit authorization
            'resizable' => true,
            'draggable' => true,
        ];
    }
}
