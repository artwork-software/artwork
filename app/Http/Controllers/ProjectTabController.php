<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ComponentInTab;
use Artwork\Modules\ProjectTab\Models\ProjectComponentValue;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Models\ProjectTabSidebarTab;
use Artwork\Modules\ProjectTab\Models\SidebarTabComponent;
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
            'components' => Component::notSpecial()->get()->groupBy('type'),
            'componentsSpecial' => Component::isSpecial()->get(),
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
        // get all tabs to calculate the order
        $lastOrder = ProjectTab::orderBy('order', 'desc')->first();
        $order = $lastOrder ? $lastOrder->order + 1 : 1;
        ProjectTab::create([
            'name' => $request->input('name'),
            'order' => $order,
        ]);
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
        $projectTab->update($request->only('name'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectTab $projectTab): void
    {
        foreach ($projectTab->components as $component) {
            ProjectComponentValue::where('component_id', $component->id)->delete();
            $component->delete();
        }
        $projectTab->delete();
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

    public function reorder(ProjectTab $projectTab, Request $request): void
    {
        // update the order of $projectTab and re-order the other tabs
        $order = $request->input('order');
        $oldOrder = $projectTab->order;
        if ($oldOrder < $order) {
            ProjectTab::where('order', '>', $oldOrder)->where('order', '<=', $order)->decrement('order');
        } else {
            ProjectTab::where('order', '>=', $order)->where('order', '<', $oldOrder)->increment('order');
        }
        $projectTab->update(['order' => $order]);
    }

    public function addComponentSidebar(ProjectTabSidebarTab $projectTabSidebarTab, Request $request): void
    {
        $order = $request->input('order');
        SidebarTabComponent::where('project_tab_sidebar_id', $projectTabSidebarTab->id)
            ->where('order', '>=', $order)->increment('order');
        $projectTabSidebarTab->componentsInSidebar()->create([
            'component_id' => $request->input('component_id'),
            'order' => $request->input('order'),
        ]);
    }
}
