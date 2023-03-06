<?php

namespace App\Http\Controllers;

use App\Models\ProjectStates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectStatesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        ProjectStates::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProjectStates  $projectStates
     * @return \Illuminate\Http\Response
     */
    public function show(ProjectStates $projectStates)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProjectStates  $projectStates
     * @return \Illuminate\Http\Response
     */
    public function edit(ProjectStates $projectStates)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProjectStates  $projectStates
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProjectStates $projectStates)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProjectStates  $projectStates
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectStates $projectStates)
    {
        $projectStates->delete();
    }

    public function forceDelete(int $id)
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);

        $projectStates->forceDelete();

        return Redirect::route('project.settings.trashed')->with('success', 'ProjectStates deleted');
    }

    public function restore(int $id)
    {
        $projectStates = ProjectStates::onlyTrashed()->findOrFail($id);

        $projectStates->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'ProjectStates restored');
    }
}
