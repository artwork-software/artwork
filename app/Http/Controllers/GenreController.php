<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class GenreController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Genre::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('Settings/GenreManagement', [
            'genres' => Genre::paginate(10)->through(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Genre::create([
            'name' => $request->name,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Genre $genre)
    {
        $genre->update($request->only('name'));

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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Genre $genre)
    {
        $genre->delete();
        return Redirect::back()->with('success', 'Genre deleted');
    }
}
