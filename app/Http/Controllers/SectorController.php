<?php

namespace App\Http\Controllers;

use Artwork\Modules\Sector\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Artwork\Core\Validation\MasterDataName;
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
        MasterDataName::validate($request);

        Sector::create([
            'name' => $request->name,
            'color' => $request->color
        ]);

        return Redirect::back();
    }

    public function update(Request $request, Sector $sector): void
    {
        MasterDataName::validate($request, updating: true);

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
