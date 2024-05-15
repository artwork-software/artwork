<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectRoleController extends Controller
{

    public function __construct(
        private readonly ProjectRoleService $projectRoleService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings/ProjectRoles', [
            'projectRoles' => ProjectRole::all()
        ]);
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
        $this->projectRoleService->createByRequest($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectRole $projectRole): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectRole $projectRole): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectRole $projectRole): void
    {
        $this->projectRoleService->updateByRequest($projectRole, $request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectRole $projectRole): void
    {
        $this->projectRoleService->delete($projectRole);
    }
}
