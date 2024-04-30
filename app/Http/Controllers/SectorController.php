<?php

namespace App\Http\Controllers;

use Artwork\Modules\Sector\Models\Sector;
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
            'color' => $request->color
        ]);

        return Redirect::back();
    }

    public function update(Request $request, Sector $sector): void
    {
        $sector->update($request->only(['name', 'color']));
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        $sector->delete();

        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $sector = Sector::onlyTrashed()->findOrFail($id);
        $sector->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $sector = Sector::onlyTrashed()->findOrFail($id);
        $sector->restore();

        return Redirect::route('projects.settings.trashed');
    }
}
