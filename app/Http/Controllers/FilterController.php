<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Filter\Services\FilterService;
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
    public function __construct(private readonly FilterService $filterService)
    {
    }

    public function index(): Collection
    {
        return $this->filterService->getPersonalFilter(Auth::user());
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['calendarFilters.eventPropertyIds.*' => 'exists:event_properties,id']);

        $filter = Filter::create([
            'name' => $request->name,
            'adjoiningNoAudience' => $request->input('calendarFilters.adjoiningNoAudience'),
            'adjoiningNotLoud' => $request->input('calendarFilters.adjoiningNotLoud'),
            'allDayFree' => $request->input('calendarFilters.allDayFree'),
            'showAdjoiningRooms' => $request->input('calendarFilters.showAdjoiningRooms'),
            'eventProperties' => count(
                ($eventPropertyIds = $request->collect('calendarFilters.eventPropertyIds'))
            ) > 0 ? $eventPropertyIds : null,
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
     * @param \Artwork\Modules\Filter\Models\Filter $filter
     * @return RedirectResponse
     */
    public function destroy(Filter $filter): RedirectResponse
    {
        $filter->delete();
        return Redirect::back();
    }
}
