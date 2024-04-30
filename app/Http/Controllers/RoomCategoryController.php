<?php

namespace App\Http\Controllers;

use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Artwork\Modules\RoomRoomCategoryMapping\Models\RoomRoomCategoryMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RoomCategoryController extends Controller
{
    public function store(Request $request): void
    {
        RoomCategory::create(['name' => $request->get('name')]);
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
