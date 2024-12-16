<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Modules\Area\Repositories\AreaRepository;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Repositories\FilterRepository;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\RoomAttribute\Repositories\RoomAttributeRepository;
use Artwork\Modules\RoomCategory\Repositories\RoomCategoryRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FilterService
{
    public const LOUD = 'LOUD';

    public const NOT_LOUD = 'NOT_LOUD';

    public const WITH_AUDIENCE = 'WITH_AUDIENCE';

    public const WITHOUT_AUDIENCE = 'WITHOUT_AUDIENCE';

    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly RoomRepository $roomRepository,
        private readonly RoomAttributeRepository $roomAttributeRepository,
        private readonly EventTypeService $eventTypeService,
        private readonly AreaRepository $areaRepository,
        private readonly RoomCategoryRepository $categoryRepository,
    ) {
    }

    public function getPersonalFilter(?User $user = null): \Illuminate\Support\Collection
    {
        //dirty compatibility hacks
        if ($user === null) {
            $user = auth()->user();
        }
        return $this->filterRepository->getPersonalFilter($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarFilterDefinitions(): array
    {
        return [
            'roomCategories' => $this->map($this->categoryRepository->getAll()),
            'roomAttributes' => $this->map($this->roomAttributeRepository->getAll()),
            'eventTypes' => $this->map($this->eventTypeService->getAll()),
            'eventAttributes' => [
                self::LOUD => 'loud',
                self::NOT_LOUD => 'not loud',
                self::WITH_AUDIENCE => 'With audience',
                self::WITHOUT_AUDIENCE => 'without audience',
            ],
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
