<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
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
        $components = Cache::remember('settings_components_not_special', 600, static function () {
            return Component::notSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled'])
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type');
        });

        $componentsSpecial = Cache::remember('settings_components_special', 600, static function () {
            return Component::isSpecial()
                ->without(['users', 'departments'])
                ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled'])
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
        $component->projectValue()->delete();

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
