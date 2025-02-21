<?php

namespace Artwork\Modules\Filter\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;

class FilterRepository extends BaseRepository
{
    public function getPersonalFilter(User $user): Collection
    {
        return Filter::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(
                fn(Filter $filter) => [
                    'id' => $filter->getAttribute('id'),
                    'name' => $filter->getAttribute('name'),
                    'rooms' => $filter->getAttribute('rooms')->map(
                        fn(Room $room) => [
                            'id' => $room->getAttribute('id'),
                            'everyone_can_book' => $room->getAttribute('everyone_can_book'),
                            'label' => $room->getAttribute('name'),
                            'room_admins' => $room->users()->wherePivot('is_admin', true)->get(),
                        ]
                    ),
                    'areas' => $filter->getAttribute('areas'),
                    'roomCategories' => $filter->getAttribute('room_categories'),
                    'roomAttributes' => $filter->getAttribute('room_attributes'),
                    'eventTypes' => $filter->getAttribute('event_types'),
                    'adjoiningNoAudience' => $filter->getAttribute('adjoiningNoAudience'),
                    'adjoiningNotLoud' => $filter->getAttribute('adjoiningNotLoud'),
                    'showAdjoiningRooms' => $filter->getAttribute('showAdjoiningRooms'),
                    'allDayFree' => $filter->getAttribute('allDayFree'),
                    'eventProperties' => $filter->getAttribute('eventProperties'),
                ]
            );
    }
}
