<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\SidebarTabComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidebarTabComponentController extends Controller
{
    private const CACHE_KEY_TABS = 'settings_tabs_with_relations';
    private const CACHE_KEY_COMPONENTS = 'settings_components_not_special';
    private const CACHE_KEY_COMPONENTS_SPECIAL = 'settings_components_special';

    /**
     * Invalidiert alle Tab-Settings-bezogenen Caches
     */
    private function clearTabSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY_TABS);
        Cache::forget(self::CACHE_KEY_COMPONENTS);
        Cache::forget(self::CACHE_KEY_COMPONENTS_SPECIAL);
    }
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
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    public function removeComponent(SidebarTabComponent $sidebarTabComponent)
    {
        try {
            $sidebarTabComponent->delete();

            $this->clearTabSettingsCache();

            return redirect()->back()->with('success', 'Komponente erfolgreich aus der Sidebar entfernt');
        } catch (\Exception $e) {
            Log::error('Fehler beim Entfernen der Komponente aus der Sidebar: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Fehler beim Entfernen der Komponente']);
        }
    }
}
