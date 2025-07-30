<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;
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

        //dd($request->all());

        $roomIds = $request->collect('rooms')->isNotEmpty() ? $request->collect('rooms') : null;
        $areaIds = $request->collect('areas')->isNotEmpty() ? $request->collect('areas') : null;
        $eventTypes = $request->collect('event_types')->isNotEmpty() ? $request->collect('event_types') : null;
        $roomAttributes = $request->collect('room_attributes')->isNotEmpty() ? $request->collect('room_attributes') : null;
        $roomCategories = $request->collect('room_categories')->isNotEmpty() ? $request->collect('room_categories') : null;
        $eventProperties = $request->collect('event_properties')->isNotEmpty() ? $request->collect('event_properties') : null;

        //$request->validate(['eventPropertyIds.*' => 'exists:event_properties,id']);

        $filter = Filter::create([
            'name' => $request->get('name'),
            'adjoiningNoAudience' => $request->boolean('adjoiningNoAudience'),
            'adjoiningNotLoud' => $request->boolean('adjoiningNotLoud'),
            'allDayFree' => $request->boolean('allDayFree'),
            'showAdjoiningRooms' => $request->boolean('showAdjoiningRooms'),
            'eventProperties' => $eventProperties,
            'user_id' => Auth::id()
        ]);


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

        if ($roomCategories) {
            foreach ($roomCategories as $roomCategoryId) {
                $filter->room_categories()->save(RoomCategory::where('id', $roomCategoryId)->first());
            }
        }

        if ($roomAttributes) {
            foreach ($roomAttributes as $roomAttributeId) {
                $filter->room_attributes()->save(RoomAttribute::where('id', $roomAttributeId)->first());
            }
        }

        if ($eventTypes) {
            foreach ($eventTypes as $eventTypeId) {
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
    public function destroy(Filter $filter): void
    {
        $filter->delete();
        //return Redirect::back();
    }

    public function activate(UserFilterTemplate $filter, User $user): void
    {
        $user->userFilters()->updateOrCreate([
            'filter_type' => $filter->filter_type
        ], [
            'room_ids' => $filter->room_ids,
            'area_ids' => $filter->area_ids,
            'room_category_ids' => $filter->room_category_ids,
            'room_attribute_ids' => $filter->room_attribute_ids,
            'event_type_ids' => $filter->event_type_ids,
            'event_property_ids' => $filter->event_property_ids,
            'craft_ids' => $filter->craft_ids,
        ]);
    }
}
