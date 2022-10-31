<?php

namespace App\Http\Resources;

use App\Builders\EventBuilder;
use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Filter;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use App\Models\RoomCategory;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $calendarFilters = json_decode($request->input('calendarFilters'));
        $allDayFree = $calendarFilters->allDayFree;

        $period = CarbonPeriod::create(Carbon::parse($request->get('start'))->addHours(2), Carbon::parse($request->get('end')));

        // Convert the period to an array of dates
        $dates = $period->toArray();
        $roomIds = [];

        foreach ($dates as $date) {
            Debugbar::info($date);
            $events = Event::query()->occursAt(Carbon::parse($date))->get();
            foreach ($events as $event) {
                $roomIds[] = $event->room_id;
            }
        }
        $room_occurrences = array_count_values($roomIds);

        $rooms_with_events_everyDay = [];
        foreach ($room_occurrences as $key => $occurrence) {
                if ($occurrence == count($dates)) {
                    $rooms_with_events_everyDay[] = $key;
                }
        }

        if($allDayFree) {
            $rooms = Room::with('adjoining_rooms', 'main_rooms')->whereNotIn('id', $rooms_with_events_everyDay)->get()->map(fn(Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area' => $room->area,
                'room_admins' => $room->room_admins,
                'everyone_can_book' => $room->everyone_can_book,
                'label' => $room->name,
                'adjoining_rooms' => $room->adjoining_rooms->map(fn(Room $adjoining_room) => [
                    'id' => $adjoining_room->id,
                    'label' => $adjoining_room->name
                ]),
                'main_rooms' => $room->main_rooms->map(fn(Room $main_room) => [
                    'id' => $main_room->id,
                    'label' => $main_room->name
                ]),
                'categories' => $room->categories,
                'attributes' => $room->attributes
            ]);
        }
        else {
            $rooms = Room::with('adjoining_rooms', 'main_rooms')->get()->map(fn(Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area' => $room->area,
                'room_admins' => $room->room_admins,
                'everyone_can_book' => $room->everyone_can_book,
                'label' => $room->name,
                'adjoining_rooms' => $room->adjoining_rooms->map(fn(Room $adjoining_room) => [
                    'id' => $adjoining_room->id,
                    'label' => $adjoining_room->name
                ]),
                'main_rooms' => $room->main_rooms->map(fn(Room $main_room) => [
                    'id' => $main_room->id,
                    'label' => $main_room->name
                ]),
                'categories' => $room->categories,
                'attributes' => $room->attributes
            ]);
        }

        return [
            'resource' => class_basename($this),
            'events' => CalendarEventResource::collection($this->collection),

            'calendarFilters' => Filter::where('user_id', Auth::id())->get()->map(fn(Filter $filter) => [
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
                'rooms' => $filter->rooms->map(fn(Room $room) => [
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

            'types' => EventType::all()->map(fn(EventType $type) => [
                'id' => $type->id,
                'label' => $type->name,
                'img' => $type->svg_name,
            ]),

            'projects' => Project::all()->map(fn(Project $project) => [
                'id' => $project->id,
                'label' => $project->name,
                'project_admins' => $project->adminUsers
            ]),

            'rooms' => $rooms,

            'roomCategories' => RoomCategory::all()->map(fn(RoomCategory $roomCategory) => [
                'id' => $roomCategory->id,
                'name' => $roomCategory->name,
            ]),

            'roomAttributes' => RoomAttribute::all()->map(fn(RoomAttribute $roomAttribute) => [
                'id' => $roomAttribute->id,
                'name' => $roomAttribute->name,
            ]),

            'eventTypes' => EventType::all()->map(fn(EventType $eventType) => [
                'id' => $eventType->id,
                'name' => $eventType->name,
            ]),

            'areas' => Area::all()->map(fn(Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
            ]),
        ];
    }
}
