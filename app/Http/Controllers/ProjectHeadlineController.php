<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectHeadline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectHeadlineController extends Controller
{

    protected ?HistoryController $history = null;


    public function __construct()
    {
        $this->history = new HistoryController('App\Models\Project');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProjectHeadline  $projectHeadline
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ProjectHeadline $projectHeadline)
    {
        $projectHeadline->update(["name" => $request->name]);

        return Redirect::back();
    }

    public function updateText(Request $request, ProjectHeadline $projectHeadline, Project $project)
    {
        $oldHeadLine = $project->headlines()->where('project_headline_id', $projectHeadline->id)->first();
        $projectHeadline->projects()->updateExistingPivot($project, array('text' => $request->text), false);
        $newHeadLine = $project->headlines()->where('project_headline_id', $projectHeadline->id)->first();
        //dd($oldHeadLine->pivot->text);
        if($oldHeadLine->pivot->text === null && $newHeadLine->pivot->text !== null){
            $this->history->createHistory($project->id, $projectHeadline->name . ' wurde hinzugefügt', 'public_changes');
        }
        if($oldHeadLine->pivot->text !== null && $newHeadLine->pivot->text === null){
            $this->history->createHistory($project->id, $projectHeadline->name . ' wurde entfernt', 'public_changes');
        }
        if($oldHeadLine->pivot->text !== null && $newHeadLine->pivot->text !== null && $oldHeadLine->pivot->text !== $newHeadLine->pivot->text){
            $this->history->createHistory($project->id, $projectHeadline->name . ' wurde geändert', 'public_changes');
        }

        $projectController = new ProjectController();
        $projectController->setPublicChangesNotification($project->id);
    }

    public function updateOrder(Request $request)
    {

        foreach ($request->headlines as $headline) {
            ProjectHeadline::findOrFail($headline['id'])->update(['order' => $headline['order']]);
        }

        return Redirect::back();
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProjectHeadline  $projectHeadline
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectHeadline $projectHeadline)
    {
        $projectHeadline->delete();

        $projects = Project::all();

        foreach ($projects as $project) {
            $project->headlines()->detach($projectHeadline);
        }
    }
}
