<?php

namespace App\Http\Controllers;

use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\Component;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\ResponseFactory|\Inertia\Response
    {
        // get all TabComponentsEnum names
        $tabComponentTypes = TabComponentEnums::getValues();
        // return inertia view with components and special components



        return inertia('Settings/ComponentManagement/Index', [
            'components' => Component::notSpecial()->get()->groupBy('type'),
            'componentsSpecial' => Component::isSpecial()->get(),
            'tabComponentTypes' => $tabComponentTypes
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
        Component::create([
            'name' => $request->name,
            'type' => $request->type,
            'data' => $request->data,
            'special' => false,
            'sidebar_enabled' => true
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Component $component): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Component $component): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Component $component): void
    {
        $component->update($request->only('name', 'data'));
    }

    /**
     * Remove the specified resource from storage.
     */
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
