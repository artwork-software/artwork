<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubEventResource extends JsonResource
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
            'id' => $this->id,
            'title' => $this->eventName,
            'description' => $this->description,
            'start' => $this->start_time,
            'end' => $this->end_time,
            'audience' => $this->audience,
            'is_loud' => $this->is_loud,
            'class' => $this->type->svg_name,
            'eventTypeName' => $this->type->name,
            'eventTypeId' => $this->type->id,
            'eventType' => $this->type,
            'eventTypeAbbreviation' =>$this->type->abbreviation,
            'allDay' => $this->allDay,
        ];
    }
}
