<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AreaController extends Controller
{

    public function __construct()
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
            'areas' => Area::all()->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                // showContent declares if the area should be showing all details when loading the page
                'showContent' => true,
                'rooms' => $area->rooms()->orderBy('order')->get()->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Area::create([
            'name' => $request->name
        ]);

        return Redirect::route('areas.management')->with('success', 'Area created.');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function show(Area $area)
    {
        //not needed
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Area $area)
    {
        $area->update($request->only('name'));
        return Redirect::route('areas.management')->with('success', 'Area updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Area $area) {

        $new_area = Area::create([
            'name' => '(Kopie) ' . $area->name
        ]);

        foreach($area->rooms as $room) {
            $new_room = $room->replicate();
            $new_room->name = '(Kopie) ' . $room->name;
            $new_room->created_at = Carbon::now();
            $new_area->rooms()->save($new_room);
            $new_area->save();
        }

        return Redirect::route('areas.management')->with('success', 'Area created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Area $area)
    {
        $area->delete();
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
            'trashed_areas' => Area::onlyTrashed()->get()->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => (bool)$room->temporary,
                    'start_date' => $room->start_date,
                    'end_date' => $room->end_date,
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ])
        ]);
    }
}
