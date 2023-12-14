<?php

namespace App\Http\Controllers;

use App\Models\RoomAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

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
        $roomAttribute->delete();

        return Redirect::route('areas.management')->with('success', 'Roomattribute deleted');
    }
}
