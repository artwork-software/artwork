<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ComponentTreeProjector;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AppProjectService
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly ComponentTreeProjector $componentTreeProjector,
    ) {
    }

    /**
     * All projects the user may view (mirrors ProjectPolicy::view as a query):
     * global `view projects` permission, project membership, or membership in
     * one of the project's departments.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjects(User $user): array
    {
        $query = Project::query()
            ->with('status')
            ->withCount('users')
            ->orderBy('name');

        if (!$user->can(PermissionEnum::PROJECT_VIEW->value)) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id))
                    ->orWhereHas('departments.users', fn (Builder $q) => $q->where('users.id', $user->id));
            });
        }

        return $query->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'is_group' => (bool) $project->is_group,
                'state' => $this->serializeState($project->status),
                'team_count' => $project->users_count,
            ])
            ->all();
    }

    /**
     * Project header for the app detail screen: name, state, team roster and
     * the tabs the tab strip offers. Tabs whose components are all web-only
     * (currently the budget spreadsheet and similar heavy views) are omitted —
     * the app shows exactly the tabs it can render.
     *
     * @return array<string, mixed>
     */
    public function getProject(User $user, Project $project): array
    {
        $project->loadMissing(['status', 'users']);

        $tabs = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->select(['id', 'name'])
            ->visibleForUser($user)
            ->whereHas(
                'components.component',
                fn (Builder $q) => $q->whereIn('type', ProjectTabComponentEnum::appReadableValues()),
            )
            ->orderBy('order')
            ->get();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'is_group' => (bool) $project->is_group,
            'artists' => $project->artists !== '' ? $project->artists : null,
            'state' => $this->serializeState($project->status),
            'team' => $this->serializeTeam($project),
            'can_edit_team' => $user->can('update', $project),
            'project_roles' => ProjectRole::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (ProjectRole $role): array => ['id' => $role->id, 'name' => $role->name])
                ->all(),
            'tabs' => $tabs
                ->map(static fn (ProjectTab $tab): array => [
                    'id' => $tab->id,
                    'name' => $tab->name,
                ])
                ->all(),
        ];
    }

    /**
     * One tab's components with the project's values, ordered like the web tab
     * view. Filters to app-renderable types and applies the component-level
     * visibility rules; `is_writable` already contains the edit decision so the
     * app never has to re-derive permissions.
     *
     * @return array<string, mixed>
     */
    public function getTabComponents(User $user, Project $project, ProjectTab $tab): array
    {
        return [
            'tab' => [
                'id' => $tab->id,
                'name' => $tab->name,
            ],
            'components' => $this->componentTreeProjector->project(
                $tab,
                [
                    'component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
                    'component.users',
                    'component.departments.users',
                    'disclosureComponents.component.projectValue' => fn ($q) => $q->where('project_id', $project->id),
                    'disclosureComponents.component.users',
                    'disclosureComponents.component.departments.users',
                ],
                fn (Component $component): bool => $this->isRenderable($user, $project, $component),
                fn (Component $component, array $scope): array =>
                    $this->serializeComponent($user, $project, $component, $scope),
            ),
        ];
    }

    private function isRenderable(User $user, Project $project, Component $component): bool
    {
        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);

        return ($type?->isAppReadable() ?? false)
            && $user->can('viewComponent', [$project, $component]);
    }

    /**
     * @param array<int, int> $scope
     * @return array<string, mixed>
     */
    private function serializeComponent(User $user, Project $project, Component $component, array $scope): array
    {
        $type = ProjectTabComponentEnum::tryFrom((string) $component->type);

        return [
            'component_id' => $component->id,
            'type' => $type?->appWireType() ?? (string) $component->type,
            'name' => $component->name,
            'data' => $this->dataObjectOrNull($component->data),
            // System components get a server-built payload; custom components
            // carry the project's stored value.
            'value' => $type?->isAppSystem()
                ? $this->systemComponentService->valueFor($user, $project, $type, $scope)
                : $this->dataObjectOrNull($component->projectValue?->data),
            // For system components this means "may write through it" (create
            // events, edit shifts, add comments); custom components combine
            // type writability with the component permission settings.
            'is_writable' => $type?->isAppSystem()
                ? $this->systemComponentService->isWritable($user, $project, $type)
                : (($type?->isAppWritable() ?? false)
                    && $user->can('writeComponent', [$project, $component])),
        ];
    }

    /**
     * The project roster with the pivot rights the app renders and edits.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeTeam(Project $project): array
    {
        $roleNames = ProjectRole::query()->pluck('name', 'id');

        return $project->users()->get()
            ->map(static fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->full_name,
                'is_manager' => (bool) $member->pivot->is_manager,
                'can_write' => (bool) $member->pivot->can_write,
                'access_budget' => (bool) $member->pivot->access_budget,
                'roles' => collect($member->pivot->roles ?? [])
                    ->map(static fn ($roleId): array => [
                        'id' => (int) $roleId,
                        'name' => (string) ($roleNames[$roleId] ?? ''),
                    ])
                    ->filter(static fn (array $role): bool => $role['name'] !== '')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Empty component data/values are stored as [] which would serialize as a
     * JSON array — the app validates them as object-or-null.
     *
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>|null
     */
    private function dataObjectOrNull(?array $data): ?array
    {
        return $data === [] ? null : $data;
    }

    /**
     * @return array{id: int, name: string, color: string|null}|null
     */
    private function serializeState(?ProjectState $state): ?array
    {
        if ($state === null) {
            return null;
        }

        return [
            'id' => $state->id,
            'name' => $state->name,
            // Stored values may be a hex color or a legacy CSS class name —
            // the wire promises hex-or-null, so legacy values are dropped here.
            'color' => $this->hexOrNull($state->color),
        ];
    }

    private function hexOrNull(?string $color): ?string
    {
        if ($color === null || preg_match('/^#?([0-9a-f]{6}|[0-9a-f]{3})$/i', $color) !== 1) {
            return null;
        }

        return '#' . ltrim($color, '#');
    }
}
