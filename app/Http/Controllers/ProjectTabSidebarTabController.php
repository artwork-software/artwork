<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProjectTabSidebarTabController extends Controller
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
    public function store(ProjectTab $projectTab, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // get all tabs to calculate the order for this specific project tab
        $projectTabSidebarTab = ProjectTabSidebarTab::where('project_tab_id', $projectTab->id)
            ->orderBy('order', 'desc')
            ->first();
        $order = $projectTabSidebarTab ? $projectTabSidebarTab->order + 1 : 1;

        try {
            $projectTab->sidebarTabs()->create([
                'name' => $validated['name'],
                'order' => $order,
            ]);

            $this->clearTabSettingsCache();

            return redirect()->back()->with('success', 'Sidebar-Tab erfolgreich erstellt');
        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen des Sidebar-Tabs: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Fehler beim Erstellen des Sidebar-Tabs']);
        }
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
    public function update(Request $request, ProjectTabSidebarTab $projectTabSidebarTab)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $projectTabSidebarTab->update($validated);

            $this->clearTabSettingsCache();

            return redirect()->back()->with('success', 'Sidebar-Tab erfolgreich aktualisiert');
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren des Sidebar-Tabs: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Fehler beim Aktualisieren des Sidebar-Tabs']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectTabSidebarTab $projectTabSidebarTab)
    {
        try {
            // Delete all components associated with this sidebar tab
            $projectTabSidebarTab->componentsInSidebar()->delete();

            // Delete the sidebar tab itself
            $projectTabSidebarTab->delete();

            $this->clearTabSettingsCache();

            return redirect()->back()->with('success', 'Sidebar-Tab erfolgreich gelöscht');
        } catch (\Exception $e) {
            Log::error('Fehler beim Löschen des Sidebar-Tabs: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Fehler beim Löschen des Sidebar-Tabs']);
        }
    }

    public function updateComponentOrder(Request $request, ProjectTabSidebarTab $projectTabSidebarTab)
    {
        try {
            $components = $request->input('components', []);

            if (empty($components)) {
                Log::warning('Keine Komponenten zum Sortieren übergeben');
                return response()->json(['message' => 'Keine Komponenten übergeben'], 400);
            }

            Log::info('Sortiere Komponenten für Sidebar-Tab: ' . $projectTabSidebarTab->id, [
                'components' => $components
            ]);

            $order = 1;
            foreach ($components as $component) {
                $updated = SidebarTabComponent::where('id', $component['id'])
                    ->where('project_tab_sidebar_id', $projectTabSidebarTab->id)
                    ->update(['order' => $order]);

                Log::info("Komponente {$component['id']} aktualisiert: Order = {$order}, Rows affected: {$updated}");
                $order++;
            }

            $this->clearTabSettingsCache();

            Log::info('Komponenten-Reihenfolge erfolgreich aktualisiert');

            // Für Inertia.js: Leere Response oder redirect
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Reihenfolge erfolgreich aktualisiert']);
            }

            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren der Komponenten-Reihenfolge: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Fehler beim Aktualisieren'], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Fehler beim Aktualisieren der Reihenfolge']);
        }
    }

    public function reorder(Request $request)
    {
        try {
            $components = $request->input('components', []);

            if (empty($components)) {
                Log::warning('Keine Sidebar-Tabs zum Sortieren übergeben');
                return response()->json(['message' => 'Keine Tabs übergeben'], 400);
            }

            Log::info('Sortiere Sidebar-Tabs', ['components' => $components]);

            $order = 1;
            foreach ($components as $component) {
                ProjectTabSidebarTab::where('id', $component['id'])->update([
                    'order' => $order,
                ]);
                $order++;
            }

            $this->clearTabSettingsCache();

            Log::info('Sidebar-Tab-Reihenfolge erfolgreich aktualisiert');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Reihenfolge erfolgreich aktualisiert']);
            }

            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren der Sidebar-Tab-Reihenfolge: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Fehler beim Aktualisieren'], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Fehler beim Aktualisieren der Reihenfolge']);
        }
    }
}
