<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;

class ComponentController extends Controller
{
    public function index(): Response
    {
        $tabComponentTypes = ProjectTabComponentEnum::getValues();
        // Komponentenlisten verschlankt und gecacht (10 Minuten)
        // Users und Departments werden nicht mehr eager geladen - stattdessen lazy loading beim Öffnen des Edit-Modals
        $components = Cache::remember('settings_components_not_special', 600, static function () {
            return Component::notSpecial()
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled', 'permission_type'])
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type');
        });

        $componentsSpecial = Cache::remember('settings_components_special', 600, static function () {
            return Component::isSpecial()
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled', 'permission_type'])
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('Settings/ComponentManagement/Index', [
            'components' => $components,
            'componentsSpecial' => $componentsSpecial,
            'tabComponentTypes' => $tabComponentTypes
        ]);
    }

    /**
     * Show a single component with its users and departments relations.
     * Used for lazy-loading when editing a component.
     */
    public function show(Component $component): JsonResponse
    {
        // Load relations only when needed (on-demand for edit modal)
        $component->load(['users', 'departments']);

        return response()->json($component);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        /** @var Component $component */
        $component = Component::create([
            'name' => $request->name,
            'type' => $request->type,
            'data' => $request->data,
            'permission_type' => $request->permission_type,
            'special' => false,
            'sidebar_enabled' => true
        ]);

        foreach ($request->get('users', []) as $user) {
            $component->users()->attach($user['user_id'], ['can_write' => $user['can_write']]);
        }

        foreach ($request->get('departments', []) as $department) {
            $component->departments()->attach($department['department_id'], ['can_write' => $department['can_write']]);
        }

        // Cache invalidieren, damit neue Komponenten sichtbar werden
        Cache::forget('settings_components_not_special');
        Cache::forget('settings_components_special');
        // Auch Drucklayout-bezogene Caches leeren
        Cache::forget('print_layout_components_not_special');
        Cache::forget('print_layout_components_special');
        Cache::forget('print_layout_all_components');
    }

    public function update(Request $request, Component $component): void
    {
        $component->users()->detach();
        foreach ($request->get('users', []) as $user) {
            $component->users()->attach($user['user_id'], ['can_write' => $user['can_write']]);
        }

        $component->departments()->detach();
        foreach ($request->get('departments', []) as $department) {
            $component->departments()->attach($department['department_id'], ['can_write' => $department['can_write']]);
        }

        $component->update($request->only('name', 'data', 'permission_type'));

        // Cache invalidieren, damit Änderungen unmittelbar sichtbar werden
        Cache::forget('settings_components_not_special');
        Cache::forget('settings_components_special');
        // Auch Drucklayout-bezogene Caches leeren
        Cache::forget('print_layout_components_not_special');
        Cache::forget('print_layout_components_special');
        Cache::forget('print_layout_all_components');
    }

    public function destroy(Component $component): void
    {
        $component->users()->detach();
        $component->departments()->detach();
        $component->componentInDisclosures()->delete();

        // first check if the component has projectValues attached
        if ($component->projectValue) {
            $component->projectValue->delete();
        }

        $component->componentInPrintLayouts()->delete();


        // now check if the component is in the sidebar or in a tab
        if ($component->sidebarTabComponent()->exists()) {
            foreach ($component->sidebarTabComponent()->get() as $sidebarTabComponent) {
                $sidebarTabComponent->delete();
            }
        }

        if ($component->tabComponent()->exists()) {
            foreach ($component->tabComponent()->get() as $tabComponent) {
                $tabComponent->delete();
            }
        }

        $component->delete();

        // Cache invalidieren, damit gelöschte Komponenten nicht weiter angezeigt werden
        Cache::forget('settings_components_not_special');
        Cache::forget('settings_components_special');
        // Auch Drucklayout-bezogene Caches leeren
        Cache::forget('print_layout_components_not_special');
        Cache::forget('print_layout_components_special');
        Cache::forget('print_layout_all_components');
    }
}
