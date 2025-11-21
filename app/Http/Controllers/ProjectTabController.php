<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;
use Illuminate\Support\Facades\Cache;

class ProjectTabController extends Controller
{
    public function list()
    {
        // Minimal list for client-side selection when creating checklists
        return response()->json(\Artwork\Modules\Project\Models\ProjectTab::query()
            ->orderBy('order')
            ->get(['id','name']));
    }

    public function index(): ResponseFactory|Response
    {
        // Tabs + Relationen gezielt und schlank laden
        $tabs = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->select(['id', 'name', 'order', 'default'])
            ->orderBy('order')
            ->with([
                // Haupt-Komponenten im Tab
                'components' => function ($query): void {
                    /** @var \Illuminate\Database\Eloquent\Builder $query */
                    $query
                        ->without(['component', 'disclosureComponents']) // Standard-$with reduzieren
                        ->select(['id', 'project_tab_id', 'component_id', 'order', 'scope', 'note'])
                        ->orderBy('order')
                        ->with([
                            // Zugehörige Component minimal laden
                            'component' => function ($q): void {
                                $q->without(['users', 'departments'])
                                    ->select(['id', 'name', 'type', 'data']);
                            },
                            // Disclosure-Komponenten inkl. zugehöriger Component
                            'disclosureComponents' => function ($q): void {
                                $q->without(['component'])
                                    ->select(['id', 'disclosure_id', 'component_id', 'order'])
                                    ->orderBy('order')
                                    ->with([
                                        'component' => function ($qc): void {
                                            $qc->without(['users', 'departments'])
                                                ->select(['id', 'name', 'type', 'data']);
                                        },
                                    ]);
                            },
                        ]);
                },
                // Sidebar-Tabs und deren Komponenten
                'sidebarTabs' => function ($query): void {
                    /** @var \Illuminate\Database\Eloquent\Builder $query */
                    $query
                        ->without(['componentsInSidebar'])
                        ->select(['id', 'project_tab_id', 'name', 'order'])
                        ->orderBy('order')
                        ->with([
                            'componentsInSidebar' => function ($q): void {
                                $q->without(['component'])
                                    ->select(['id', 'project_tab_sidebar_id', 'component_id', 'order'])
                                    ->orderBy('order')
                                    ->with([
                                        'component' => function ($qc): void {
                                            $qc->without(['users', 'departments'])
                                                ->select(['id', 'name', 'type', 'data']);
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->get();

        // Komponentenlisten verschlankt und optional gecacht
        $components = Cache::remember('settings_components_not_special', 600, function () {
            return Component::notSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data'])
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type');
        });

        $componentsSpecial = Cache::remember('settings_components_special', 600, function () {
            return Component::isSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data'])
                ->orderBy('name')
                ->get();
        });

        return inertia('Settings/ProjectTab/Index', [
            'tabs' => $tabs,
            'components' => $components,
            'componentsSpecial' => $componentsSpecial,
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

    public function updateDefault(ProjectTab $projectTab): void
    {
        ProjectTab::where('default', true)->update(['default' => false]);
        $projectTab->update(['default' => true]);
    }

    public function updateComponentNote(ComponentInTab $componentInTab, Request $request): void {
        $componentInTab->update($request->only('note'));
    }

    public function updateComponentScope(ComponentInTab $componentInTab, Request $request): void
    {
        $componentInTab->update([
            'scope' => $request->input('scope', []),
        ]);
    }

    public function addDisclosureComponent(Request $request): void {
        // Verschiebe alle bestehenden Elemente mit gleicher oder größerer Order nach oben
        DisclosureComponents::where('disclosure_id', $request->get('disclosure_id'))
            ->where('order', '>=', $request->get('order'))
            ->increment('order');

        // Erst danach das neue Element mit der übergebenen Order einfügen
        DisclosureComponents::create([
            'component_id' => $request->get('component_id'),
            'disclosure_id' => $request->get('disclosure_id'),
            'order' => $request->get('order'),
        ]);
    }

    public function removeComponentFormDisclosure(Request $request): void {
        // Verschiebe alle bestehenden Elemente mit größerer Order nach oben
        DisclosureComponents::find($request->get('id'))->delete();
    }

}
