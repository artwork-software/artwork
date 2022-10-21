<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectIndexAdminResource;
use App\Http\Resources\RoomCalendarResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
use App\Models\Area;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RoomController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        /** @var Room $room */
        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'temporary' => $request->temporary,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'area_id' => $request->area_id,
            'user_id' => $request->user_id,
            'everyone_can_book' => $request->everyone_can_book,
            'order' => Room::max('order') + 1,
        ]);

        $room->adjoiningRooms()->sync(collect($request->adjoining_rooms)->pluck("id"));

        $room->attributes()->sync(collect($request->room_attributes)->pluck("id"));

        $room->categories()->sync(collect($request->room_categories)->pluck("id"));

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

    /**
     * Display the specified resource.
     *
     * @param Room $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room, Request $request)
    {
        $room->load('creator');
        $projects = Project::query()->with(['adminUsers', 'managerUsers'])->get();

        return inertia('Rooms/Show', [
            'room' => new RoomCalendarResource($room),
            'is_room_admin' => $room->room_admins->contains(Auth::id()),
            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),

            'available_categories' => RoomCategory::all(),
            'roomCategoryIds' => $room->categories()->pluck('room_category_id'),
            'roomCategories' => $room->categories,

            'available_attributes' => RoomAttribute::all(),
            'roomAttributeIds' => $room->attributes()->pluck('room_attribute_id'),
            'roomAttributes' => $room->attributes,

            'available_rooms' => Room::where('id', '!=', $room->id)->get(),
            'adjoiningRoomIds' => $room->adjoiningRooms()->pluck('adjoining_room_id'),
            'adjoiningRooms' => $room->adjoiningRooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Room $room
     * @return RedirectResponse
     */
    public function update(Request $request, Room $room)
    {
        $room->update($request->only('name', 'description', 'temporary', 'start_date', 'end_date', 'everyone_can_book'));

        $room->room_admins()->sync(
            collect($request->room_admins)
                ->map(function ($room_admin) {
                    $this->authorize('update', User::find($room_admin['id']));

                    return $room_admin['id'];
                })
        );

        $room->adjoiningRooms()->sync($request->adjoining_rooms);

        $room->attributes()->sync($request->room_attributes);

        $room->categories()->sync($request->room_categories);

        return Redirect::back()->with('success', 'Room updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Room $room)
    {
        Room::create([
            'name' => '(Kopie) ' . $room->name,
            'description' => $room->description,
            'temporary' => $room->temporary,
            'start_date' => $room->start_date,
            'end_date' => $room->end_date,
            'area_id' => $room->area_id,
            'user_id' => Auth::id(),
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Room $room
     * @return RedirectResponse
     */
    public function updateOrder(Request $request)
    {
        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    public function getTrashed()
    {
        return inertia('Trash/Rooms', [
            'trashed_rooms' => Area::all()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexWithoutEventsResource::collection($area->trashed_rooms)->resolve(),
            ])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Room $room
     * @return RedirectResponse
     */
    public function destroy(Room $room)
    {
        $room->delete();

        return Redirect::route('areas.management')->with('success', 'Room moved to trash');
    }

    public function forceDelete(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();

        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }

    public function restore(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->restore();

        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }
}
