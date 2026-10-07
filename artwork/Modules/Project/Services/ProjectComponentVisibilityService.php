<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serverseitige Sichtregel für Projekt-Tabs und ihre Komponenten — Spiegel von canSeeComponent()
 * im Frontend (resources/js/Composeables/Permission.js):
 * - Tab-Sichtbarkeit: ProjectTab::visibleForUser (Admins sehen alle Tabs).
 * - Komponenten-Berechtigung ("Sehen dürfen nur die Folgenden"): Component::isVisibleTo; Admins und
 *   "write projects" sind davon ausgenommen.
 * Projektzugriff selbst prüft weiterhin ProjectPolicy::view (CanViewProject).
 *
 * Scoped (je Request/Job eine Instanz): Policies werden je Prüfung neu gebaut, ohne geteilte Instanz
 * griff der Cache visibleTabIdsByUser nie (N+1 in Checklisten- und Datei-Policies).
 */
#[Scoped]
class ProjectComponentVisibilityService
{
    /** @var array<int, Collection<int, int>> */
    private array $visibleTabIdsByUser = [];

    public function bypassesComponentPermissions(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value) ||
            $user->can(PermissionEnum::WRITE_PROJECTS->value);
    }

    public function canSeeComponent(User $user, Component $component): bool
    {
        return $this->bypassesComponentPermissions($user) || $component->isVisibleTo($user);
    }

    /**
     * @return Collection<int, int>
     */
    public function visibleTabIds(User $user): Collection
    {
        return $this->visibleTabIdsByUser[$user->id] ??= ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->visibleForUser($user)
            ->pluck('id');
    }

    public function canSeeTab(User $user, int $tabId): bool
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        return $this->visibleTabIds($user)->contains($tabId);
    }

    /**
     * Schränkt die Tab-Auswahl einer Platzierung (Dokumente/Kommentare/To-do-Liste) auf die Tabs ein,
     * die die Person sehen darf.
     *
     * @param array<int, int|string> $scope
     * @return array<int, int>
     */
    public function restrictScopeToVisibleTabs(User $user, array $scope): array
    {
        $visibleTabIds = $this->visibleTabIds($user);

        return collect($scope)
            ->map(fn ($tabId) => (int) $tabId)
            ->filter(fn (int $tabId) => $visibleTabIds->contains($tabId))
            ->values()
            ->all();
    }

    /**
     * Inhalte ohne Tab (tab_id = null) oder aus sichtbaren Tabs. Admins sehen alles — auch Inhalte
     * gelöschter eingeschränkter Tabs, deren tab_id auf keinen Tab mehr zeigt.
     */
    public function constrainToVisibleTabs(Builder|Relation $query, User $user): void
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return;
        }

        $visibleTabIds = $this->visibleTabIds($user);

        $query->where(function ($query) use ($visibleTabIds): void {
            $query->whereIn('tab_id', $visibleTabIds)->orWhereNull('tab_id');
        });
    }

    /**
     * Entfernt Komponenten, die die Person nicht sehen darf, samt ihrer Projektwerte aus dem
     * Tab-Payload (Hauptbereich, Ordnerinhalte, Seitenleisten).
     */
    public function filterTabPayload(ProjectTab $projectTab, User $user): void
    {
        $isVisible = fn ($placement): bool => $placement->component !== null &&
            $this->canSeeComponent($user, $placement->component);

        $components = $projectTab->components->filter($isVisible)->values();
        $components->each(function (ComponentInTab $placement) use ($isVisible): void {
            if ($placement->relationLoaded('disclosureComponents')) {
                $placement->setRelation(
                    'disclosureComponents',
                    $placement->disclosureComponents->filter($isVisible)->values()
                );
            }
        });
        $projectTab->setRelation('components', $components);

        $projectTab->sidebarTabs->each(function (ProjectTabSidebarTab $sidebarTab) use ($isVisible): void {
            $sidebarTab->setRelation(
                'componentsInSidebar',
                $sidebarTab->componentsInSidebar->filter($isVisible)->values()
            );
        });
    }

    /**
     * Löst die Platzierung einer Dokumente-/Kommentar-/To-do-Komponente für die Daten-Endpunkte auf.
     * Platzierungen in Ordnern liegen in einer eigenen Tabelle mit eigenen Ids — sie werden nur über
     * $inFolder angesprochen, damit nie eine Tab-Platzierung mit gleicher Id greift. Ordner-
     * Platzierungen werden als ComponentInTab mit der gespeicherten Tab-Auswahl zurückgegeben.
     */
    public function resolvePlacement(User $user, int $placementId, bool $inFolder, string $expectedType): ComponentInTab
    {
        if ($inFolder) {
            $folderPlacement = DisclosureComponents::query()->find($placementId);
            if ($folderPlacement === null || $folderPlacement->component?->type !== $expectedType) {
                throw new NotFoundHttpException();
            }
            if (!$this->canSeeFolderPlacement($user, $folderPlacement)) {
                throw new AccessDeniedHttpException();
            }

            $placement = new ComponentInTab();
            $placement->id = $folderPlacement->id;
            $placement->component_id = $folderPlacement->component_id;
            $placement->scope = $folderPlacement->scope;
            $placement->setRelation('component', $folderPlacement->component);

            return $placement;
        }

        $placement = ComponentInTab::query()->find($placementId);
        if ($placement === null || $placement->component?->type !== $expectedType) {
            throw new NotFoundHttpException();
        }
        if (
            !$this->canSeeTab($user, $placement->project_tab_id) ||
            !$this->canSeeComponent($user, $placement->component)
        ) {
            throw new AccessDeniedHttpException();
        }

        return $placement;
    }

    /**
     * Sichtbar, wenn die Person die Komponente sehen darf und sie — falls sie überhaupt in einem Tab
     * liegt — in mindestens einem für sie sichtbaren Tab liegt (direkt, im Ordner oder in der
     * Seitenleiste). Für Drucklayouts, die Komponenten ohne Tab-Bezug ausgeben.
     */
    public function canSeeInProject(User $user, Component $component): bool
    {
        return $this->visibleInProjectComponentIds($user, [$component])->contains($component->id);
    }

    /**
     * Sammelvariante von canSeeInProject() (Drucklayout, Projektübersicht): Platzierungen aller
     * Komponenten werden mit einer festen Zahl Abfragen geladen statt je Komponente.
     *
     * @param iterable<int, Component|null> $components
     * @return Collection<int, int> IDs der sichtbaren Komponenten
     */
    public function visibleInProjectComponentIds(User $user, iterable $components): Collection
    {
        $components = EloquentCollection::make($components)->filter()->unique('id')->values();
        if ($components->isEmpty()) {
            return collect();
        }

        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return $components->pluck('id')->values();
        }

        $permitted = $this->filterComponentsVisibleTo($user, $components);
        $placementTabIds = $this->placementTabIdsByComponent($user, $permitted->pluck('id'));
        $visibleTabIds = $this->visibleTabIds($user);

        return $permitted
            ->filter(function (Component $component) use ($placementTabIds, $visibleTabIds): bool {
                $tabIds = $placementTabIds[$component->id] ?? null;

                return $tabIds === null || $tabIds->intersect($visibleTabIds)->isNotEmpty();
            })
            ->pluck('id')
            ->values();
    }

    /**
     * Für die typgebundenen Daten-Endpunkte (Team, Status, Budget …): sichtbar, wenn die Person
     * mindestens eine Komponente dieser Typen sehen darf, die in einem für sie sichtbaren Tab liegt
     * (direkt, im Ordner oder in der Seitenleiste). Nicht platzierte Typen zeigt keine Oberfläche an.
     *
     * @param array<int, string> $types
     */
    public function canSeeComponentTypeInProject(User $user, array $types): bool
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        $permitted = $this->filterComponentsVisibleTo($user, Component::query()->whereIn('type', $types)->get());
        $placementTabIds = $this->placementTabIdsByComponent($user, $permitted->pluck('id'));
        $visibleTabIds = $this->visibleTabIds($user);

        return $permitted->contains(
            fn (Component $component) => ($placementTabIds[$component->id] ?? collect())
                ->intersect($visibleTabIds)
                ->isNotEmpty()
        );
    }

    private function canSeeFolderPlacement(User $user, DisclosureComponents $folderPlacement): bool
    {
        $folder = Component::query()->find($folderPlacement->disclosure_id);
        if (
            $folder === null ||
            !$this->canSeeComponent($user, $folder) ||
            !$this->canSeeComponent($user, $folderPlacement->component)
        ) {
            return false;
        }

        return ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->where('component_id', $folder->id)
            ->whereIn('project_tab_id', $this->visibleTabIds($user))
            ->exists();
    }

    /**
     * Komponenten-Berechtigung ("Sehen dürfen nur die Folgenden") für viele Komponenten; Personen und
     * Abteilungen werden nur für eingeschränkte Komponenten und gesammelt nachgeladen.
     *
     * @param EloquentCollection<int, Component> $components
     * @return EloquentCollection<int, Component>
     */
    private function filterComponentsVisibleTo(User $user, EloquentCollection $components): EloquentCollection
    {
        if ($this->bypassesComponentPermissions($user)) {
            return $components;
        }

        $restricted = $components->filter(fn (Component $component) => $component->permission_type ===
            ProjectTabComponentPermissionEnum::PERMISSION_TYPE_SOME_SEE_SOME_EDIT->value);
        if ($restricted->isNotEmpty()) {
            $restricted->loadMissing(['users', 'departments.users']);
        }

        return $components->filter(fn (Component $component) => $component->isVisibleTo($user))->values();
    }

    /**
     * Tabs, in denen die Komponenten liegen (direkt, in der Seitenleiste oder in einem für die Person
     * sichtbaren Ordner). Nicht platzierte Komponenten fehlen im Ergebnis (Bedeutung: kein Tab-Bezug).
     *
     * @param Collection<int, int> $componentIds
     * @return array<int, Collection<int, int>>
     */
    private function placementTabIdsByComponent(User $user, Collection $componentIds): array
    {
        if ($componentIds->isEmpty()) {
            return [];
        }

        $directPlacements = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->whereIn('component_id', $componentIds)
            ->get(['component_id', 'project_tab_id']);

        $sidebarPlacements = SidebarTabComponent::query()
            ->without('component')
            ->whereIn('component_id', $componentIds)
            ->get(['component_id', 'project_tab_sidebar_id']);
        $tabIdBySidebarId = $sidebarPlacements->isEmpty()
            ? collect()
            : ProjectTabSidebarTab::query()
                ->without('componentsInSidebar')
                ->whereIn('id', $sidebarPlacements->pluck('project_tab_sidebar_id')->unique())
                ->pluck('project_tab_id', 'id');

        $folderPlacements = DisclosureComponents::query()
            ->without('component')
            ->whereIn('component_id', $componentIds)
            ->get(['component_id', 'disclosure_id']);
        $tabIdsByVisibleFolder = $this->tabIdsOfVisibleFolders($user, $folderPlacements->pluck('disclosure_id'));

        $tabIds = [];
        foreach ($directPlacements as $placement) {
            $tabIds[$placement->component_id][] = (int) $placement->project_tab_id;
        }
        foreach ($sidebarPlacements as $placement) {
            // Verwaiste Seitenleisten-Einträge (Seitenleiste gelöscht) gelten nicht als Platzierung
            if ($tabIdBySidebarId->has($placement->project_tab_sidebar_id)) {
                $tabIds[$placement->component_id][] = (int) $tabIdBySidebarId[$placement->project_tab_sidebar_id];
            }
        }
        foreach ($folderPlacements as $placement) {
            // Auch ein unsichtbarer Ordner macht die Komponente zu einer platzierten (ohne sichtbaren Tab)
            $tabIds[$placement->component_id] = array_merge(
                $tabIds[$placement->component_id] ?? [],
                $tabIdsByVisibleFolder[$placement->disclosure_id] ?? []
            );
        }

        return array_map(fn (array $ids) => collect($ids)->unique()->values(), $tabIds);
    }

    /**
     * @param Collection<int, int> $folderIds
     * @return array<int, array<int, int>> Tab-IDs je Ordner, nur für Ordner, die die Person sehen darf
     */
    private function tabIdsOfVisibleFolders(User $user, Collection $folderIds): array
    {
        if ($folderIds->isEmpty()) {
            return [];
        }

        $visibleFolderIds = $this->filterComponentsVisibleTo(
            $user,
            Component::query()->whereIn('id', $folderIds->unique())->get()
        )->pluck('id');
        if ($visibleFolderIds->isEmpty()) {
            return [];
        }

        $tabIds = [];
        $folderPlacements = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->whereIn('component_id', $visibleFolderIds)
            ->get(['component_id', 'project_tab_id']);
        foreach ($folderPlacements as $placement) {
            $tabIds[$placement->component_id][] = (int) $placement->project_tab_id;
        }

        return $tabIds;
    }
}
