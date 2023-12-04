<?php

namespace App\Http\Controllers;

use App\Models\Genre;
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
        ]);
        return Redirect::back()->with('success', 'Genre created');
    }

    public function update(Request $request, Genre $genre): void
    {
        $genre->update($request->only('name'));

        /*
        if (Auth::user()->can('update projects')) {
            $genre->projects()->sync(
                collect($request->assigned_project_ids)
                    ->map(function ($project_id) {
                        return $project_id;
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign projects to a genre.'], 403);
        }
        */
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();
        return Redirect::back()->with('success', 'Genre deleted');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $genre = Genre::onlyTrashed()->findOrFail($id);

        $genre->forceDelete();

        return Redirect::route('projects.settings.trashed')->with('success', 'Genre deleted');
    }

    public function restore(int $id): RedirectResponse
    {
        $genre = Genre::onlyTrashed()->findOrFail($id);

        $genre->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'Genre restored');
    }
}
