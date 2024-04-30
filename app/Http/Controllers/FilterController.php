<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

/**
 * @todo: Use FilterService and its FilterRepository
 */
class FilterController extends Controller
{
    public function index(): Collection
    {
        return Filter::query()->where('user_id', Auth::id())->get()->map(fn (Filter $filter) => [
            'id' => $filter->id,
            'name' => $filter->name,
            'rooms' => $filter->rooms->map(fn (Room $room) => [
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

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function store(Request $request): RedirectResponse
    {
        $filter = Filter::create([
            'name' => $request->name,
            'isLoud' => $request->input('calendarFilters.isLoud'),
            'isNotLoud' => $request->input('calendarFilters.isNotLoud'),
            'hasAudience' => $request->input('calendarFilters.hasAudience'),
            'hasNoAudience' => $request->input('calendarFilters.hasNoAudience'),
            'adjoiningNoAudience' => $request->input('calendarFilters.adjoiningNoAudience'),
            'adjoiningNotLoud' => $request->input('calendarFilters.adjoiningNotLoud'),
            'allDayFree' => $request->input('calendarFilters.allDayFree'),
            'showAdjoiningRooms' => $request->input('calendarFilters.showAdjoiningRooms'),
            'user_id' => Auth::id()
        ]);

        $roomIds = $request->input('calendarFilters.roomIds');
        $areaIds = $request->input('calendarFilters.areaIds');
        $roomCategoryIds = $request->input('calendarFilters.roomCategoryIds');
        $roomAttributeIds = $request->input('calendarFilters.roomAttributeIds');
        $eventTypeIds = $request->input('calendarFilters.eventTypeIds');

        if ($roomIds) {
            foreach ($roomIds as $roomId) {
                $filter->rooms()->save(Room::where('id', $roomId)->first());
            }
        }

        if ($areaIds) {
            foreach ($areaIds as $areaId) {
                $filter->areas()->save(Area::where('id', $areaId)->first());
            }
        }

        if ($roomCategoryIds) {
            foreach ($roomCategoryIds as $roomCategoryId) {
                $filter->room_categories()->save(RoomCategory::where('id', $roomCategoryId)->first());
            }
        }

        if ($roomAttributeIds) {
            foreach ($roomAttributeIds as $roomAttributeId) {
                $filter->room_attributes()->save(RoomAttribute::where('id', $roomAttributeId)->first());
            }
        }

        if ($eventTypeIds) {
            foreach ($eventTypeIds as $eventTypeId) {
                $filter->event_types()->save(EventType::where('id', $eventTypeId)->first());
            }
        }

        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Filter\Models\Filter  $filter
     * @return RedirectResponse
     */
    public function destroy(Filter $filter): RedirectResponse
    {
        $filter->delete();
        return Redirect::back();
    }
}
