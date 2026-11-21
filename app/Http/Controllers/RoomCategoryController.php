<?php

namespace App\Http\Controllers;

use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\Room\Models\RoomRoomCategoryMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class RoomCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('room_categories', 'name')],
        ]);

        RoomCategory::create(['name' => $validated['name']]);

        return Redirect::back();
    }

    public function update(Request $request, RoomCategory $roomCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('room_categories', 'name')->ignore($roomCategory->id),
            ],
        ]);

        $roomCategory->update(['name' => $validated['name']]);

        return Redirect::back();
    }

    public function destroy(RoomCategory $roomCategory): RedirectResponse
    {
        foreach (
            RoomRoomCategoryMapping::query()
                ->where('room_category_id', '=', $roomCategory->id)
                ->get() as $roomRoomCategoryMapping
        ) {
            $roomRoomCategoryMapping->delete();
        }

        $roomCategory->delete();

        return Redirect::back();
    }
}
