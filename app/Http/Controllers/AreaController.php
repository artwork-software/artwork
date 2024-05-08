<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Room\Http\Resources\RoomIndexResource;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class AreaController extends Controller
{
    public function __construct(private readonly AreaService $areaService)
    {
        $this->authorizeResource(Area::class);
    }

    public function index(): Response|ResponseFactory
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

    public function store(Request $request): RedirectResponse
    {
        $this->areaService->createByRequest($request);
        return Redirect::route('areas.management');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $this->areaService->updateByRequest($area, $request);
        return Redirect::route('areas.management');
    }

    public function duplicate(Area $area, RoomService $roomService): RedirectResponse
    {
        $this->areaService->duplicateByAreaModel($area, $roomService);
        return Redirect::route('areas.management');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $this->areaService->delete($area);
        return Redirect::route('areas.management');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $area = Area::onlyTrashed()->findOrFail($id);
        $area->forceDelete();
        return Redirect::route('areas.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        /** @var Area $area */
        $area = Area::onlyTrashed()->findOrFail($id);
        $area->restore();
        foreach ($area->rooms() as $room) {
            $room->restore();
        }
        return Redirect::route('areas.trashed');
    }

    public function getTrashed(): Response|ResponseFactory
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
