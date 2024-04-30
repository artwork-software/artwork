<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectStatesController extends Controller
{
    public function store(Request $request): void
    {
        ProjectStates::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
    }

    public function destroy(ProjectStates $projectStates): void
    {
        $projectStates->delete();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);
        $projectStates->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);
        $projectStates->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, ProjectStates $projectStates): void
    {
        $projectStates->update($request->only(['name', 'color']));
    }
}
