<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectHeadline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectHeadlineController extends Controller
{

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
        $projectHeadline->projects()->updateExistingPivot($project, array('text' => $request->text), false);
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
