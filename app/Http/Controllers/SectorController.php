<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SectorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Sector::class);
    }

    public function store(Request $request): RedirectResponse
    {
        Sector::create([
            'name' => $request->name,
        ]);

        return Redirect::back()->with('success', 'Sector created');
    }

    public function update(Request $request, Sector $sector): void
    {
        $sector->update($request->only('name'));
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        $sector->delete();

        return Redirect::back()->with('success', 'Sector deleted');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $sector = Sector::onlyTrashed()->findOrFail($id);
        $sector->forceDelete();

        return Redirect::route('projects.settings.trashed')->with('success', 'Sector deleted');
    }

    public function restore(int $id): RedirectResponse
    {
        $sector = Sector::onlyTrashed()->findOrFail($id);
        $sector->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'Sector restored');
    }
}
