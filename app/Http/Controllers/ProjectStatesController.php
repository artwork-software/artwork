<?php

namespace App\Http\Controllers;


use Artwork\Modules\Project\Models\ProjectStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectStatesController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
        ProjectStates::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
    }


    public function show(ProjectStates $projectStates): void
    {
    }


    public function edit(ProjectStates $projectStates): void
    {
    }

    public function update(Request $request, ProjectStates $projectStates): void
    {
    }


    public function destroy(ProjectStates $projectStates): void
    {
        $projectStates->delete();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);
        $projectStates->forceDelete();

        return Redirect::route('projects.settings.trashed')->with('success', 'ProjectStates deleted');
    }

    public function restore(int $id): RedirectResponse
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);
        $projectStates->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'ProjectStates restored');
    }
}
