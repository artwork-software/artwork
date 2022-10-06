<?php

namespace App\Http\Resources;

use App\Models\Area;
use App\Models\EventType;
use App\Models\Filter;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use App\Models\RoomCategory;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

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

            'calendarFilters' => Filter::where('user_id', Auth::id())->get()->map(fn (Filter $filter) => [
                'id' => $filter->id,
                'name' => $filter->name,
                'isLoud' => $filter->isLoud,
                'isNotLoud' => $filter->isNotLoud,
                'hasAudience' => $filter->hasAudience,
                'hasNoAudience' => $filter->hasNoAudience,
                'adjoiningNoAudience' => $filter->adjoiningNoAudience,
                'adjoiningNotLoud' => $filter->adjoiningNotLoud,
                'allDayFree' => $filter->allDayFree,
                'showAdjoiningRooms' => $filter->showAdjoiningRooms,
                'rooms' => $filter->rooms->map(fn (Room $room) => [
                    'id' => $room->id,
                    'everyone_can_book' => $room->everyone_can_book,
                    'label' => $room->name,
                    'room_admins' => $room->room_admins
                ]),
                'areas' => $filter->areas,
                'roomCategories' => $filter->room_categories,
                'roomAttributes' => $filter->room_attributes,
                'eventTypes' => $filter->event_types,
            ]),

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

            'room_categories' => RoomCategory::all()->map(fn (RoomCategory $roomCategory) => [
                'id' => $roomCategory->id,
                'name' => $roomCategory->name,
            ]),

            'room_attributes' => RoomAttribute::all()->map(fn (RoomAttribute $roomAttribute) => [
                'id' => $roomAttribute->id,
                'name' => $roomAttribute->name,
            ]),

            'event_types' => EventType::all()->map(fn (EventType $eventType) => [
                'id' => $eventType->id,
                'name' => $eventType->name,
            ]),

            'areas' => Area::all()->map(fn (Area $area) => [
                'id' => $area->id,
                'label' => $area->name,
            ]),
        ];
    }
}
