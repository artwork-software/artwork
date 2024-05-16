<?php

namespace App\Http\Controllers;

use Artwork\Modules\Genre\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GenreController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Genre::class);
    }

    public function store(Request $request): RedirectResponse
    {
        Genre::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Genre\Models\Genre  $genre
     * @return RedirectResponse
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->only(['name', 'color']));

        return Redirect::back();
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $genre = Genre::onlyTrashed()->findOrFail($id);

        $genre->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $genre = Genre::onlyTrashed()->findOrFail($id);

        $genre->restore();

        return Redirect::route('projects.settings.trashed');
    }
}
