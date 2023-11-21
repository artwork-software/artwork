<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoomIndexResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
use App\Models\User;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;

class AreaController extends Controller
{

    public function __construct(private AreaService $areaService)
    {
        $this->authorizeResource(Area::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('Areas/AreaManagement', [
            'areas' => Area::all()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                // showContent declares if the area should be showing all details when loading the page
                'showContent' => true,
                'rooms' => RoomIndexResource::collection($area->rooms()->orderBy('order')->get())->resolve(),
            ]),
            'opened_areas' => User::where('id', Auth::id())->first()->opened_areas,
            'room_categories' => RoomCategory::all(),
            'room_attributes' => RoomAttribute::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $this->areaService->createByRequest($request);
        return Redirect::route('areas.management')->with('success', 'Area created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Area  $area
     * @return \Illuminate\Http\Response
     */
    public function show(Area $area)
    {
        //not needed
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Area  $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Area $area)
    {
        $this->areaService->updateByRequest($area, $request);
        return Redirect::route('areas.management')->with('success', 'Area updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Area $area)
    {
        $this->areaService->duplicateByAreaModel($area);
        return Redirect::route('areas.management')->with('success', 'Area created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Area  $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Area $area)
    {
        $this->areaService->delete($area);
        return Redirect::route('areas.management')->with('success', 'Area moved to trash');
    }

    public function forceDelete(int $id)
    {
        $area = Area::onlyTrashed()->findOrFail($id);

        $area->forceDelete();

        return Redirect::route('areas.trashed')->with('success', 'Room restored');
    }

    public function restore(int $id)
    {
        $area = Area::onlyTrashed()->findOrFail($id);

        $area->restore();
        foreach ($area->rooms() as $room) {
            $room->restore();
        }

        return Redirect::route('areas.trashed')->with('success', 'Room restored');
    }

    public function getTrashed()
    {
        return inertia('Trash/Areas', [
            'trashed_areas' => Area::onlyTrashed()->get()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexWithoutEventsResource::collection($area->rooms)->resolve(),
            ])
        ]);
    }
}
