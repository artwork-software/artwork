<?php

namespace Artwork\Modules\Filter\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

class FilterRepository extends BaseRepository
{
    public function getPersonalFilter(User $user): \Illuminate\Support\Collection
    {
        return Filter::query()->where('user_id', $user->id)->get()->map(fn(Filter $filter) => [
            'id' => $filter->id,
            'name' => $filter->name,
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
            'isLoud' => $filter->isLoud,
            'isNotLoud' => $filter->isNotLoud,
            'hasAudience' => $filter->hasAudience,
            'hasNoAudience' => $filter->hasNoAudience,
            'adjoiningNoAudience' => $filter->adjoiningNoAudience,
            'adjoiningNotLoud' => $filter->adjoiningNotLoud,
            'showAdjoiningRooms' => $filter->showAdjoiningRooms,
            'allDayFree' => $filter->allDayFree
        ]);
    }
}
