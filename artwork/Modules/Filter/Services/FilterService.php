<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Repositories\AreaRepository;
use Artwork\Modules\EventType\Repositories\EventTypeRepository;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\RoomAttribute\Repositories\RoomAttributeRepository;
use Artwork\Modules\RoomCategory\Repositories\RoomCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class FilterService
{
    public function __construct(
        private readonly RoomCategoryRepository $roomCategoryRepository,
        private readonly RoomAttributeRepository $roomAttributeRepository,
        private readonly EventTypeRepository $eventTypeRepository,
        private readonly AreaRepository $areaRepository,
        private readonly RoomRepository $roomRepository
    ) {
    }

    public function getCalendarFilterDefinitions(): array
    {
        return [
            'roomCategories' =>  $this->map($this->roomCategoryRepository->getAll()),
            'roomAttributes' => $this->map($this->roomAttributeRepository->getAll()),
            'eventTypes' => $this->map($this->eventTypeRepository->getAll()),
            'areas' => $this->map($this->areaRepository->getAll()),
            'rooms' => $this->roomRepository
                ->allWithoutTrashed()
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
