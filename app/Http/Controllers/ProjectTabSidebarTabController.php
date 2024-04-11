<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\ComponentInTab;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Models\ProjectTabSidebarTab;
use Artwork\Modules\ProjectTab\Models\SidebarTabComponent;
use Illuminate\Http\Request;

class ProjectTabSidebarTabController extends Controller
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
    public function store(ProjectTab $projectTab, Request $request): void
    {
        // get all tabs to calculate the order
        $projectTabSidebarTab = ProjectTabSidebarTab::orderBy('order', 'desc')->first();
        $order = $projectTabSidebarTab ? $projectTabSidebarTab->order + 1 : 1;

        $projectTab->sidebarTabs()->create([
            'name' => $request->input('name'),
            'order' => $order,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectTabSidebarTab $projectTabSidebarTab): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectTabSidebarTab $projectTabSidebarTab): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectTabSidebarTab $projectTabSidebarTab): void
    {
        $projectTabSidebarTab->update($request->only(['name']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectTabSidebarTab $projectTabSidebarTab): void
    {
        //
    }

    public function updateComponentOrder(Request $request, ProjectTabSidebarTab $projectTabSidebarTab): void
    {
        $order = 1;
        foreach ($request->input('components') as $component) {
            SidebarTabComponent::where('id', $component['id'])
                ->where('project_tab_sidebar_id', $projectTabSidebarTab->id)->update([
                'order' => $order,
            ]);
            $order++;
        }
    }

    public function reorder(Request $request): void
    {
        $order = 1;
        foreach ($request->input('components') as $component) {
            ProjectTabSidebarTab::where('id', $component['id'])->update([
                'order' => $order,
                ]);
            $order++;
        }
    }
}
