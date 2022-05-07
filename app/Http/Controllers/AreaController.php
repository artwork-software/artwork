<?php

namespace App\Http\Controllers;

use App\Models\Area;
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
            'areas' => Area::paginate(10)->through(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'start_date' => $room->start_date,
                    'end_date' => $room->end_date,
                    'created_at' => $room->created_at,
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

    public function forceDelete(Area $area)
    {

        $area->forceDelete();
        return Redirect::route('areas.management')->with('success', 'Area deleted permanently');
    }

    public function restore(Area $area)
    {

        $area->restore();
        foreach ($area->rooms() as $room) {
            $room->restore();
        }
        return Redirect::route('areas.management')->with('success', 'Area restored');
    }

    public function getTrashed()
    {
        return inertia('Trash/Areas', [
            'trashed_areas' => Area::onlyTrashed()->paginate(10)->through(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'start_date' => $room->start_date,
                    'end_date' => $room->end_date,
                    'created_at' => $room->created_at,
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ])
        ]);
    }
}
