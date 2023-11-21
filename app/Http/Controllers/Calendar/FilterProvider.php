<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Resources\ResourceMappers\SimpleMapping;
use App\Models\EventType;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;

class FilterProvider
{
    use SimpleMapping;

    public function provide(): array
    {
        $roomCategories = RoomCategory::all();
        $roomAttributes = RoomAttribute::all();
        $eventTypes = EventType::all();
        $areas = Area::all();
        return [
            'projects' => Project::all()->map(fn(Project $project) => [
                'id' => $project->id,
                'label' => $project->name,
                'access_budget' => $project->access_budget
            ]),
            'roomCategories' =>  $this->map($roomCategories),
            'roomAttributes' => $this->map($roomAttributes),
            'eventTypes' => $this->map($eventTypes),
            'areas' => $this->map($areas),

            'rooms' => Room::with('adjoining_rooms', 'main_rooms')->get()->map(fn(Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area' => $areas->where('id', $room->area_id),
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
            ]),
        ];
    }
}
