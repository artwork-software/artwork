<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ComponentInTab;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Illuminate\Http\Request;

class ProjectTabController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\ResponseFactory|\Inertia\Response
    {
        return inertia('Settings/ProjectTab/Index', [
            'tabs' => ProjectTab::with([
                'components' => function ($query): void {
                    $query->orderBy('order');
                },
            ])->orderBy('order')->get(),
            'components' => Component::all(),
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectTab $projectTab): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectTab $projectTab): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectTab $projectTab): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectTab $projectTab): void
    {
        //
    }

    public function updateComponentOrder(ProjectTab $projectTab, Request $request): void
    {
        $order = 1;
        foreach ($request->input('components') as $component) {
            ComponentInTab::where('id', $component['id'])->where('project_tab_id', $projectTab->id)->update([
                'order' => $order,
            ]);
            $order++;
        }
    }

    public function addComponent(ProjectTab $projectTab, Request $request): void
    {

        $order = $request->input('order');
        ComponentInTab::where('project_tab_id', $projectTab->id)->where('order', '>=', $order)->increment('order');
        $projectTab->components()->create([
            'component_id' => $request->input('component_id'),
            'order' => $request->input('order'),
        ]);
    }

    public function removeComponent(ProjectTab $projectTab, Request $request): void
    {
        $projectTab->components()->where('id', $request->input('component_id'))->delete();
    }
}
