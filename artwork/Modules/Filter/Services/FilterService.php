<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Repositories\FilterRepository;
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
        RoomService $roomService
    ): array {
        return [
            'roomCategories' =>  $this->map($roomCategoryService->getAll()),
            'roomAttributes' => $this->map($roomAttributeService->getAll()),
            'eventTypes' => $this->map($eventTypeService->getAll()),
            'areas' => $this->map($areaService->getAll()),
            'rooms' => $roomService
                ->getAllWithoutTrashed()
                ->map(fn(Room $room) => [
                    'id' => $room->getAttribute('id'),
                    'name' => $room->getAttribute('name'),
                    'label' => $room->getAttribute('name'),
                ]),
        ];
    }

    private function map(Collection $collection): Collection|\Illuminate\Support\Collection
    {
        return $collection->map(fn(Model $model) => [
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
        ]);
    }
}
