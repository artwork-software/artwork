<?php

namespace App\Http\Controllers;

use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomRoomAttributeMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class RoomAttributeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('room_attributes', 'name')],
        ]);

        RoomAttribute::create(['name' => $validated['name']]);

        return Redirect::back();
    }

    public function update(Request $request, RoomAttribute $roomAttribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('room_attributes', 'name')->ignore($roomAttribute->id),
            ],
        ]);

        $roomAttribute->update(['name' => $validated['name']]);

        return Redirect::back();
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
