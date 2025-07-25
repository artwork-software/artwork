<?php

namespace App\Http\Controllers;

use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomRoomAttributeMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RoomAttributeController extends Controller
{
    public function store(Request $request): void
    {
        RoomAttribute::create(['name' => $request->get('name')]);
    }

    public function destroy(RoomAttribute $roomAttribute): RedirectResponse
    {
        foreach (
            RoomRoomAttributeMapping::query()
                ->where('room_attribute_id', '=', $roomAttribute->id)
                ->get() as $roomRoomAttributeMapping
        ) {
            $roomRoomAttributeMapping->delete();
        }

        $roomAttribute->delete();

        return Redirect::back();
    }
}
