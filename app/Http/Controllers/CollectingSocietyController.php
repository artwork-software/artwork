<?php

namespace App\Http\Controllers;

use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CollectingSocietyController extends Controller
{
    public function index(): Collection
    {
        return CollectingSociety::all();
    }

    public function store(Request $request): void
    {
        CollectingSociety::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
    }

    public function destroy(CollectingSociety $collectingSociety): RedirectResponse
    {
        $collectingSociety->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $collectingSociety = CollectingSociety::onlyTrashed()->findOrFail($id);

        $collectingSociety->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $collectingSociety = CollectingSociety::onlyTrashed()->findOrFail($id);

        $collectingSociety->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, CollectingSociety $collectingSociety): RedirectResponse
    {
        $collectingSociety->update($request->only(['name', 'color']));

        return Redirect::back();
    }
}
