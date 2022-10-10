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
        $contentString = '';
        $classString = '';
        if($this->occupancy_option){
            $classString = $this->event_type->svg_name . ' ' . 'occupancy_option';
        }else{
            $classString = $this->event_type->svg_name;
        }
        if($this->audience){
            if($this->is_loud){
                $contentString = '<div class="flex absolute right-0 top-0">
                                    <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>
                                    <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-6 w-6 mx-1" alt="isLoudIcon"/>
                                  </div>
                               ';
            }else{
                $contentString = '<div class="flex absolute right-0 top-0">
                                    <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>
                                  </div>
                               ';
            }
        }else{
            if($this->is_loud){
                $contentString = '<div class="flex absolute right-0 top-0">
                                    <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-6 w-6 mx-2" alt="isLoudIcon"/>
                                  </div>
                               ';
            }
        }
        return [
            'resource' => class_basename($this),
            'id' => $this->id,

            'start' => $this->start_time->utc()->toIso8601String(),
            'end' => $this->end_time->utc()->toIso8601String(),
            'title' => $this->project?->name ?: $this->name? : $this->event_type->name,
            'description' => $this->description,
            'audience' => $this->audience,
            'isLoud' => $this->is_loud,
            'projectId' => $this->project_id,
            'projectName' => $this->project?->name,
            'roomId' => $this->room_id,
            'eventTypeId' => $this->event_type_id,
            'class' => $classString,
            'areaId' => $this->room?->area_id,
            'created_at' => $this->created_at->format('d.m.Y, H:i'),
            'created_by' => $this->creator,
            'content' => $contentString,
            'occupancy_option' => $this->occupancy_option,

            'collisionCount'=> $this->collision_count,

            // to display rooms as split
            'split' => $this->room_id,

            // Todo Add Authorization
            'resizable' => true,
            'draggable' => true,

            'canEdit' => Auth::user()->can('update', $this->resource),
            'canAccept' => Auth::user()->can('update', $this->resource),
            'canDelete' => Auth::user()->can('delete', $this->resource),
        ];
    }
}
