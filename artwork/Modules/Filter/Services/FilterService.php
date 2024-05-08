<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Repositories\FilterRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

readonly class FilterService
{
    public function __construct(private FilterRepository $filterRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarFilterDefinitions(
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        ProjectService $projectService,
        RoomService $roomService
    ): array {
        $roomCategories = $roomCategoryService->getAll();
        $roomAttributes = $roomAttributeService->getAll();
        $eventTypes = $eventTypeService->getAll();
        $areas = $areaService->getAll();
        return [
            'projects' => $projectService->getAll()->map(fn(Project $project) => [
                'id' => $project->id,
                'label' => $project->name,
                'access_budget' => $project->access_budget
            ]),
            'roomCategories' =>  $this->map($roomCategories),
            'roomAttributes' => $this->map($roomAttributes),
            'eventTypes' => $this->map($eventTypes),
            'areas' => $this->map($areas),
            'rooms' => $roomService
                ->getAllWithoutTrashed(['adjoining_rooms', 'main_rooms'])
                ->map(
                    fn(Room $room) => [
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
                    ]
                ),
        ];
    }

    private function map(Collection $collection): Collection|\Illuminate\Support\Collection
    {
        return $collection->map(fn(Model $model) => [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }
}
