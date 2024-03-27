<?php

namespace App\Http\Controllers;

use App\Models\RoomRoomAttributeMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Artwork\Modules\Room\Models\RoomAttribute;

class RoomAttributeController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
        RoomAttribute::create(['name' => $request->get('name')]);
    }


    public function show(): void
    {
    }


    public function edit(): void
    {
    }


    public function update(): void
    {
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
