<?php

namespace App\Http\Resources;

use App\Models\Area;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin \App\Models\Event
 */
class CalendarEventCollectionResource extends ResourceCollection
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
            'events' => CalendarEventResource::collection($this->collection),

            'types' => EventType::all()->map(fn (EventType $type) => [
                'id' => $type->id,
                'label' => $type->name,
                'img' => $type->svg_name,
            ]),

            'projects' => Project::all()->map(fn (Project $project) => [
                'id' => $project->id,
                'label' => $project->name,
                'project_admins' => $project->adminUsers

            ]),

            'rooms' => Room::all()->map(fn (Room $room) => [
                'id' => $room->id,
                'label' => $room->name,
                'room_admins' => $room->room_admins,
                'everyone_can_book' => $room->everyone_can_book
            ]),

            'areas' => Area::all()->map(fn (Area $area) => [
                'id' => $area->id,
                'label' => $area->name,
            ]),
        ];
    }
}
