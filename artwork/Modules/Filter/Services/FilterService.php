<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Modules\Area\Repositories\AreaRepository;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Repositories\EventPropertyRepository;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Repositories\FilterRepository;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Repositories\RoomAttributeRepository;
use Artwork\Modules\Room\Repositories\RoomCategoryRepository;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FilterService
{
    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly RoomRepository $roomRepository,
        private readonly RoomAttributeRepository $roomAttributeRepository,
        private readonly EventTypeService $eventTypeService,
        private readonly AreaRepository $areaRepository,
        private readonly RoomCategoryRepository $categoryRepository,
        private readonly EventPropertyRepository $eventPropertyRepository,
        private readonly CraftService $craftService,
        private readonly AuthManager $authManager
    ) {
    }

    public function getPersonalFilter(?User $user = null, string $filterType = UserFilterTypes::CALENDAR_FILTER->value): Collection
    {
        $foundedUser = $user;
        //dirty compatibility hacks
        if ($foundedUser === null) {
            $foundedUser = $this->authManager->user();
        }
        return $foundedUser->userFilterTemplates()->where('filter_type', $filterType)->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarFilterDefinitions(): array
    {
        $roomCategories = $this->categoryRepository->getAll();
        $roomAttributes = $this->roomAttributeRepository->getAll();
        $eventTypes = $this->eventTypeService->getAll();
        $eventProperties = $this->eventPropertyRepository->getAll();
        $crafts = $this->craftService->getAll();

        return [
            'room_category_ids' => $this->map($roomCategories),
            'room_attribute_ids' => $this->map($roomAttributes),
            'event_type_ids' => $this->map($eventTypes),
            'event_property_ids' => $this->map($eventProperties),
            'area_ids' => $this->map($this->areaRepository->getAll()),
            'room_ids' => $this->roomRepository
                ->allWithoutTrashed()
                ->map(fn(Room $room) => [
                    'id' => $room->getAttribute('id'),
                    'name' => $room->getAttribute('name'),
                    'label' => $room->getAttribute('name'),
                    'relevant_for_disposition' => (bool) $room->getAttribute('relevant_for_disposition'),
                ]),
            'craft_ids' => $this->map($crafts),
        ];
    }

    private function map(Collection $collection): Collection|\Illuminate\Support\Collection
    {
        return $collection->map(fn(Model $model) => [
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'icon' => $model?->getAttribute('icon'),
        ]);
    }
}
