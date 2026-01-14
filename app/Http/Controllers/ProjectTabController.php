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
use Illuminate\Support\Facades\Log;

class ProjectTabController extends Controller
{
    private const CACHE_TTL = 600; // 10 Minuten
    private const CACHE_KEY_TABS = 'settings_tabs_with_relations';
    private const CACHE_KEY_COMPONENTS = 'settings_components_not_special';
    private const CACHE_KEY_COMPONENTS_SPECIAL = 'settings_components_special';

    public function list()
    {
        // Minimal list for client-side selection when creating checklists
        return response()->json(\Artwork\Modules\Project\Models\ProjectTab::query()
            ->orderBy('order')
            ->get(['id','name']));
    }

    public function index(): ResponseFactory|Response
    {
        // Tabs mit allen Relationen cachen (Tab Settings ändern sich selten)
        $tabs = Cache::remember(self::CACHE_KEY_TABS, self::CACHE_TTL, function () {
            return ProjectTab::query()
                ->without(['components', 'sidebarTabs'])
                ->select(['id', 'name', 'order', 'default', 'visible_for_all'])
                ->orderBy('order')
                ->with([
                    'visibleUsers' => function ($q): void {
                        /** @var \Illuminate\Database\Eloquent\Builder $q */
                        $q->select('users.id', 'users.first_name', 'users.last_name');
                    },
                    'visibleDepartments' => function ($q): void {
                        /** @var \Illuminate\Database\Eloquent\Builder $q */
                        $q->select('departments.id', 'departments.name', 'departments.svg_name');
                    },

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
        });

        // Komponentenlisten verschlankt und gecacht
        $components = Cache::remember(self::CACHE_KEY_COMPONENTS, self::CACHE_TTL, function () {
            return Component::notSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data', 'sidebar_enabled', 'special'])
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type');
        });

        $componentsSpecial = Cache::remember(self::CACHE_KEY_COMPONENTS_SPECIAL, self::CACHE_TTL, function () {
            return Component::isSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data', 'sidebar_enabled', 'special'])
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
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'visible_for_all' => ['required','boolean'],
            'visible_user_ids' => ['array'],
            'visible_user_ids.*' => ['integer','exists:users,id'],
            'visible_department_ids' => ['array'],
            'visible_department_ids.*' => ['integer','exists:departments,id'],
        ]);

        $lastOrder = ProjectTab::orderBy('order', 'desc')->first();
        $order = $lastOrder ? $lastOrder->order + 1 : 1;

        /** @var ProjectTab $tab */
        $tab = ProjectTab::create([
            'name' => $data['name'],
            'order' => $order,
            'visible_for_all' => $data['visible_for_all'],
        ]);

        if (!$data['visible_for_all']) {
            $tab->visibleUsers()->sync($data['visible_user_ids'] ?? []);
            $tab->visibleDepartments()->sync($data['visible_department_ids'] ?? []);
        } else {
            $tab->visibleUsers()->detach();
            $tab->visibleDepartments()->detach();
        }

        $this->clearTabSettingsCache();
    }


    public function update(Request $request, ProjectTab $projectTab): void
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'visible_for_all' => ['required','boolean'],
            'visible_user_ids' => ['array'],
            'visible_user_ids.*' => ['integer','exists:users,id'],
            'visible_department_ids' => ['array'],
            'visible_department_ids.*' => ['integer','exists:departments,id'],
        ]);

        $projectTab->update([
            'name' => $data['name'],
            'visible_for_all' => $data['visible_for_all'],
        ]);

        if (!$data['visible_for_all']) {
            $projectTab->visibleUsers()->sync($data['visible_user_ids'] ?? []);
            $projectTab->visibleDepartments()->sync($data['visible_department_ids'] ?? []);
        } else {
            $projectTab->visibleUsers()->detach();
            $projectTab->visibleDepartments()->detach();
        }

        $this->clearTabSettingsCache();
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

        $this->clearTabSettingsCache();
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

        $this->clearTabSettingsCache();
    }

    public function addComponent(ProjectTab $projectTab, Request $request): void
    {

        $order = $request->input('order');
        ComponentInTab::where('project_tab_id', $projectTab->id)->where('order', '>=', $order)->increment('order');
        $projectTab->components()->create([
            'component_id' => $request->input('component_id'),
            'order' => $request->input('order'),
        ]);

        $this->clearTabSettingsCache();
    }

    public function removeComponent(ProjectTab $projectTab, Request $request): void
    {
        $projectTab->components()->where('id', $request->input('component_id'))->delete();

        $this->clearTabSettingsCache();
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

        $this->clearTabSettingsCache();
    }

    public function addComponentSidebar(ProjectTabSidebarTab $projectTabSidebarTab, Request $request)
    {
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'order' => 'required|integer|min:1',
        ]);

        try {
            // Prüfe ob die Komponente bereits in diesem Sidebar-Tab existiert
            $exists = SidebarTabComponent::where('project_tab_sidebar_id', $projectTabSidebarTab->id)
                ->where('component_id', $validated['component_id'])
                ->exists();

            if ($exists) {
                return redirect()->back()->withErrors(['error' => 'Diese Komponente ist bereits im Sidebar-Tab vorhanden']);
            }

            // Prüfe ob die Komponente Sidebar-fähig ist
            $component = Component::find($validated['component_id']);
            if (!$component->sidebar_enabled) {
                return redirect()->back()->withErrors(['error' => 'Diese Komponente kann nicht in die Sidebar gelegt werden']);
            }

            // Prüfe ob es sich um eine Ordnerkomponente handelt
            if ($component->type === 'DisclosureComponent') {
                return redirect()->back()->withErrors(['error' => 'Ordnerkomponenten können nicht in die Sidebar gelegt werden']);
            }

            // Verschiebe alle Komponenten mit höherer Order um 1 nach oben
            $order = $validated['order'];
            SidebarTabComponent::where('project_tab_sidebar_id', $projectTabSidebarTab->id)
                ->where('order', '>=', $order)
                ->increment('order');

            // Erstelle die neue Komponente
            $projectTabSidebarTab->componentsInSidebar()->create([
                'component_id' => $validated['component_id'],
                'order' => $order,
            ]);

            $this->clearTabSettingsCache();

            return redirect()->back()->with('success', 'Komponente erfolgreich zur Sidebar hinzugefügt');
        } catch (\Exception $e) {
            Log::error('Fehler beim Hinzufügen der Komponente zur Sidebar: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Fehler beim Hinzufügen der Komponente zur Sidebar']);
        }
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

        $this->clearTabSettingsCache();
    }

    public function updateDefault(ProjectTab $projectTab): void
    {
        ProjectTab::where('default', true)->update(['default' => false]);
        $projectTab->update(['default' => true]);

        $this->clearTabSettingsCache();
    }

    public function updateComponentNote(ComponentInTab $componentInTab, Request $request): void
    {
        $componentInTab->update($request->only('note'));

        $this->clearTabSettingsCache();
    }

    public function updateComponentScope(ComponentInTab $componentInTab, Request $request): void
    {
        $componentInTab->update([
            'scope' => $request->input('scope', []),
        ]);

        $this->clearTabSettingsCache();
    }

    public function addDisclosureComponent(Request $request): void
    {
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

        $this->clearTabSettingsCache();
    }

    public function addDisclosureComponentWithScopes(Request $request): void
    {
        // Verschiebe alle bestehenden Elemente mit gleicher oder größerer Order nach oben
        DisclosureComponents::where('disclosure_id', $request->get('disclosure_id'))
            ->where('order', '>=', $request->get('order'))
            ->increment('order');

        // Erst danach das neue Element mit der übergebenen Order und Scope einfügen
        DisclosureComponents::create([
            'component_id' => $request->get('component_id'),
            'disclosure_id' => $request->get('disclosure_id'),
            'order' => $request->get('order'),
            'scope' => $request->get('scope'),
        ]);

        $this->clearTabSettingsCache();
    }

    public function removeComponentFormDisclosure(Request $request): void
    {
        // Verschiebe alle bestehenden Elemente mit größerer Order nach oben
        DisclosureComponents::find($request->get('id'))->delete();

        $this->clearTabSettingsCache();
    }

    /**
     * Invalidiert alle Tab-Settings-bezogenen Caches
     */
    private function clearTabSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY_TABS);
        Cache::forget(self::CACHE_KEY_COMPONENTS);
        Cache::forget(self::CACHE_KEY_COMPONENTS_SPECIAL);
    }
}
