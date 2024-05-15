<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/ProjectRoles', [
            'projectRoles' => ProjectRole::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        ProjectRole::create($request->only(['name']));
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectRole $projectRole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectRole $projectRole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectRole $projectRole)
    {
        $projectRole->update($request->only(['name']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectRole $projectRole)
    {
        // remove the project role form all project users
        DB::table('project_user')->whereJsonContains('roles', $projectRole->id)->update([
            'roles' => DB::raw('JSON_REMOVE(roles, "$[0]")')
        ]);

        $projectRole->delete();
    }
}
