<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ComponentInTab;
use Artwork\Modules\ProjectTab\Models\ProjectComponentValue;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Models\ProjectTabSidebarTab;
use Artwork\Modules\ProjectTab\Models\SidebarTabComponent;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class ProjectTabController extends Controller
{
    public function index(): ResponseFactory|Response
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

    public function update(Request $request, ProjectTab $projectTab): void
    {
        $projectTab->update($request->only('name'));
    }

    public function destroy(ProjectTab $projectTab): void
    {
        // check if tab in ComponentsInTab in scopes and delete them
        $componentsInTabWithTabAsScope = ComponentInTab::whereJsonContains('scope', $projectTab->id)->get();
        // now remove the tab from the scope
        foreach ($componentsInTabWithTabAsScope as $componentInTab) {
            $componentInTab->scope = array_diff($componentInTab->scope, [$projectTab->id]);
            $componentInTab->save();
        }

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

    public function reorder(Request $request): void
    {
        $order = 1;
        foreach ($request->input('components') as $component) {
            ProjectTab::where('id', $component['id'])->update([
                'order' => $order,
            ]);
            $order++;
        }
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

    public function addComponentWithScopes(ProjectTab $projectTab, Request $request): void
    {
        $order = $request->input('order');
        ComponentInTab::where('project_tab_id', $projectTab->id)->where('order', '>=', $order)->increment('order');
        $projectTab->components()->create([
            'component_id' => $request->input('component_id'),
            'order' => $request->input('order'),
            'scope' => $request->input('scope'),
        ]);
    }
}
