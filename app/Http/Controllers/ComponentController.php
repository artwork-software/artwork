<?php

namespace App\Http\Controllers;

use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\Component;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComponentController extends Controller
{
    public function index(): Response
    {
        $tabComponentTypes = TabComponentEnums::getValues();
        return Inertia::render('Settings/ComponentManagement/Index', [
            'components' => Component::notSpecial()->get()->groupBy('type'),
            'componentsSpecial' => Component::isSpecial()->get(),
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
    }

    public function destroy(Component $component): void
    {
        // first check if the component has projectValues attached
        if ($component->projectValue()->exists()) {
            foreach ($component->projectValue()->get() as $projectValue) {
                $projectValue->delete();
            }
        }

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
    }
}
