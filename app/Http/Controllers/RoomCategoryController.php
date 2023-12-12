<?php

namespace App\Http\Controllers;

use App\Models\RoomCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RoomCategoryController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
        RoomCategory::create(['name' => $request->get('name')]);
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

    public function destroy(RoomCategory $roomCategory): RedirectResponse
    {
        $roomCategory->delete();
        return Redirect::route('areas.management')->with('success', 'Roomcategory deleted');
    }
}
