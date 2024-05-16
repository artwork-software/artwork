<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Project\Http\Resources\ProjectCalendarShowEventResource;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarEventCollectionResource extends ResourceCollection
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        /** @var CalendarEventDto $resource */
        $resource = $this->resource;

        return [
            'resource' => class_basename($this),
            'events' => ProjectCalendarShowEventResource::collection($resource->events),
            'calendarFilters' => $resource->filter->map(fn(Filter $filter) => [
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
                    'room_admins' => $room->users()->wherePivot('is_admin', true)->get(),
                ]),
                'areas' => $filter->areas,
                'roomCategories' => $filter->room_categories,
                'roomAttributes' => $filter->room_attributes,
                'eventTypes' => $filter->event_types,
            ]),
            'types' => $resource->eventTypes,
            'projects' => $resource->projects,
            'rooms' => Room::with('adjoining_rooms', 'main_rooms')->get()->map(fn(Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area' => $room->area,
                'room_admins' => $room->users()->wherePivot('is_admin', true)->get(),
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
            ]),
            'roomCategories' => $resource->roomCategories,
            'roomAttributes' =>  $resource->roomAttributes,
            'eventTypes' => $resource->eventTypes,
            'areas' => $resource->areas,
        ];
    }
}
