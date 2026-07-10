<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Illuminate\Support\Facades\Cache;

class ComponentUsageService
{
    public const CACHE_KEY = 'settings_component_usages';

    private const CACHE_TTL = 600;

    /**
     * Verwendungs-Map für die Einstellungsseiten:
     * component_id => Liste von Einsatzorten {tab, folder|null, sidebar|null}
     *
     * @return array<int, array<int, array{tab: string, folder: string|null, sidebar: string|null}>>
     */
    public function getUsages(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $tabNames = ProjectTab::query()->pluck('name', 'id');

            $usages = [];

            $componentsInTabs = ComponentInTab::query()
                ->without(['component', 'disclosureComponents'])
                ->get(['id', 'project_tab_id', 'component_id']);

            foreach ($componentsInTabs as $componentInTab) {
                $tabName = $tabNames[$componentInTab->project_tab_id] ?? null;
                if ($tabName === null) {
                    continue;
                }
                $usages[$componentInTab->component_id][] = [
                    'tab' => $tabName,
                    'folder' => null,
                    'sidebar' => null,
                ];
            }

            // Ordner-Kinder: disclosure_id zeigt auf die component_id der Ordner-Komponente.
            // Der Einsatzort ergibt sich aus allen Tabs, in denen dieser Ordner platziert ist.
            $folderComponents = Component::query()
                ->without(['users', 'departments'])
                ->where('type', 'DisclosureComponent')
                ->get(['id', 'name', 'data'])
                ->keyBy('id');

            $folderPlacements = $componentsInTabs
                ->whereIn('component_id', $folderComponents->keys())
                ->groupBy('component_id');

            $disclosureChildren = DisclosureComponents::query()
                ->without(['component'])
                ->get(['id', 'disclosure_id', 'component_id']);

            foreach ($disclosureChildren as $child) {
                $folder = $folderComponents[$child->disclosure_id] ?? null;
                if ($folder === null) {
                    continue;
                }
                $folderLabel = $folder->data['label'] ?? $folder->name;

                foreach ($folderPlacements[$child->disclosure_id] ?? [] as $placement) {
                    $tabName = $tabNames[$placement->project_tab_id] ?? null;
                    if ($tabName === null) {
                        continue;
                    }
                    $usages[$child->component_id][] = [
                        'tab' => $tabName,
                        'folder' => $folderLabel,
                        'sidebar' => null,
                    ];
                }
            }

            $sidebarTabs = ProjectTabSidebarTab::query()
                ->get(['id', 'project_tab_id', 'name'])
                ->keyBy('id');

            $sidebarComponents = SidebarTabComponent::query()
                ->without(['component'])
                ->get(['id', 'project_tab_sidebar_id', 'component_id']);

            foreach ($sidebarComponents as $sidebarComponent) {
                $sidebarTab = $sidebarTabs[$sidebarComponent->project_tab_sidebar_id] ?? null;
                if ($sidebarTab === null) {
                    continue;
                }
                $tabName = $tabNames[$sidebarTab->project_tab_id] ?? null;
                if ($tabName === null) {
                    continue;
                }
                $usages[$sidebarComponent->component_id][] = [
                    'tab' => $tabName,
                    'folder' => null,
                    'sidebar' => $sidebarTab->name,
                ];
            }

            return $usages;
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
