<?php

namespace App\Http\Controllers;

use App\Support\Services\NewHistoryService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectHeadline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class ProjectHeadlineController extends Controller
{
    protected ?NewHistoryService $history = null;

    public function __construct()
    {
        $this->history = new NewHistoryService('Artwork\Modules\Project\Models\Project');
    }

    public function store(Request $request): RedirectResponse
    {
        $project_headline = ProjectHeadline::create([
            "name" => $request->name,
            'order' => ProjectHeadline::max('order') + 1,
        ]);

        $projects = Project::all();

        foreach ($projects as $project) {
            $project->headlines()->attach($project_headline);
        }

        return Redirect::back();
    }


    public function update(Request $request, ProjectHeadline $projectHeadline): RedirectResponse
    {
        $projectHeadline->update(["name" => $request->name]);

        return Redirect::back();
    }

    public function updateText(
        Request $request,
        ProjectHeadline $projectHeadline,
        Project $project,
        ProjectController $projectController
    ): void {
        $oldHeadLine = $project->headlines()->where('project_headline_id', $projectHeadline->id)->first();
        $projectHeadline->projects()->updateExistingPivot($project, array('text' => nl2br($request->text)), false);
        $newHeadLine = $project->headlines()->where('project_headline_id', $projectHeadline->id)->first();

        if ($oldHeadLine->pivot->text === null && $newHeadLine->pivot->text !== null) {
            $this->history->createHistory(
                $project->id,
                'Headline added',
                [$projectHeadline->name],
                'public_changes'
            );
        }
        if ($oldHeadLine->pivot->text !== null && $newHeadLine->pivot->text === null) {
            $this->history->createHistory(
                $project->id,
                'Headline removed',
                [$projectHeadline->name],
                'public_changes'
            );
        }
        if (
            $oldHeadLine->pivot->text !== null &&
            $newHeadLine->pivot->text !== null &&
            $oldHeadLine->pivot->text !== $newHeadLine->pivot->text
        ) {
            $this->history->createHistory(
                $project->id,
                'Headline text modified',
                [$projectHeadline->name],
                'public_changes'
            );
        }

        $projectController->setPublicChangesNotification($project->id);
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        foreach ($request->headlines as $headline) {
            ProjectHeadline::findOrFail($headline['id'])->update(['order' => $headline['order']]);
        }

        return Redirect::back();
    }

    public function destroy(ProjectHeadline $projectHeadline): void
    {
        $projectHeadline->delete();

        $projects = Project::all();

        foreach ($projects as $project) {
            $project->headlines()->detach($projectHeadline);
        }
    }
}
