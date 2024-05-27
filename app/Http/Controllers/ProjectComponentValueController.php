<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectComponentValue;
use Illuminate\Http\Request;

class ProjectComponentValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectComponentValue $projectComponentValue): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Component $component): void
    {
        $value = ProjectComponentValue::where('project_id', $project->id)
            ->where('component_id', $component->id)->first();

        //dd($request->input('data')['text']);
        $valueInput = null;
        if (array_key_exists('text', $request->input('data'))) {
            // add nl2br to the text input
            $valueInput = nl2br($request->input('data')['text']);
            // return it ad array to be able to store it in the database
            $valueInput = ['text' => $valueInput];
        } else {
            $valueInput = $request->input('data');
        }

        if ($value === null) {
            ProjectComponentValue::create([
                'project_id' => $project->id,
                'component_id' => $component->id,
                'data' => $valueInput,
            ]);
        } else {
            $value->update([
                'data' => $valueInput,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectComponentValue $projectComponentValue): void
    {
        //
    }
}
