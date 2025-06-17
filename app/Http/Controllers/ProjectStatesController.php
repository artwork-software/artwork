<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectStatesController extends Controller
{
    public function store(Request $request): void
    {
        ProjectState::create([
            'name' => $request->name,
            'color' => $request->color,
            'is_planning' => $request->is_planning ?? false
        ]);
    }

    public function destroy(ProjectState $projectStates): void
    {
        $projectStates->delete();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $projectStates = ProjectState::onlyTrashed()->findOrFail($id);
        $projectStates->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $projectStates = ProjectState::onlyTrashed()->findOrFail($id);
        $projectStates->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, ProjectState $projectStates): void
    {
        $projectStates->update($request->only(['name', 'color', 'is_planning']));
    }
}
