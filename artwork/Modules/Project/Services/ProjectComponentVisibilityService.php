<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
 */
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
     * Inhalte ohne Tab (tab_id = null) oder aus sichtbaren Tabs.
     */
    public function constrainToVisibleTabs(Builder|Relation $query, User $user): void
    {
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
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        if (!$this->canSeeComponent($user, $component)) {
            return false;
        }

        $tabIds = $this->placementTabIds($user, $component);

        return $tabIds === null || $tabIds->intersect($this->visibleTabIds($user))->isNotEmpty();
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
     * Tabs, in denen die Komponente liegt; null, wenn sie in keinem Tab platziert ist.
     *
     * @return Collection<int, int>|null
     */
    private function placementTabIds(User $user, Component $component): ?Collection
    {
        $directTabIds = ComponentInTab::query()
            ->without(['component', 'disclosureComponents'])
            ->where('component_id', $component->id)
            ->pluck('project_tab_id');

        $sidebarTabIds = ProjectTabSidebarTab::query()
            ->without('componentsInSidebar')
            ->whereHas('componentsInSidebar', fn (Builder $query) => $query->where('component_id', $component->id))
            ->pluck('project_tab_id');

        $folderIds = DisclosureComponents::query()
            ->without('component')
            ->where('component_id', $component->id)
            ->pluck('disclosure_id');

        if ($directTabIds->isEmpty() && $sidebarTabIds->isEmpty() && $folderIds->isEmpty()) {
            return null;
        }

        $visibleFolderIds = Component::query()
            ->whereIn('id', $folderIds)
            ->get()
            ->filter(fn (Component $folder) => $this->canSeeComponent($user, $folder))
            ->pluck('id');

        $folderTabIds = $visibleFolderIds->isEmpty()
            ? collect()
            : ComponentInTab::query()
                ->without(['component', 'disclosureComponents'])
                ->whereIn('component_id', $visibleFolderIds)
                ->pluck('project_tab_id');

        return $directTabIds->concat($sidebarTabIds)->concat($folderTabIds)->unique()->values();
    }
}
