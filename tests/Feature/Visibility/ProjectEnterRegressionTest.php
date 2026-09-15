<?php

namespace Tests\Feature\Visibility;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-visibility-scopes: Die Projektuebersicht bildet die canEnter-Flags jetzt ueber
 * Project::scopeVisibleFor statt ueber eine eigene Abfrage im Controller. Dieser Test haelt fest,
 * dass sich dabei nichts an der Menge geaendert hat — fuer drei verschiedene Personen.
 */
final class ProjectEnterRegressionTest extends TestCase
{
    /**
     * Die Abfrage, die bis core-visibility-scopes in ProjectController::mapProjectsToComponents
     * stand (Stand `git show HEAD~1`), als Referenz.
     *
     * @return Collection<int, int>
     */
    private function legacyEnterableIds(User $user, Collection $projectIds): Collection
    {
        if ($user->canAny(['view projects', 'write projects'])) {
            return $projectIds->values();
        }

        return Project::query()
            ->whereIn('id', $projectIds)
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('users', fn (Builder $userQuery) => $userQuery->where('users.id', $user->id))
                    ->orWhereHas(
                        'departments.users',
                        fn (Builder $departmentQuery) => $departmentQuery->where('users.id', $user->id)
                    );
            })
            ->pluck('id');
    }

    /** @return Collection<int, int> */
    private function scopeEnterableIds(User $user, Collection $projectIds): Collection
    {
        return Project::query()
            ->visibleFor($user)
            ->whereIn('id', $projectIds)
            ->pluck('id');
    }

    #[Test]
    public function the_scope_returns_the_same_projects_as_the_previous_controller_query(): void
    {
        $teamProject = Project::factory()->create();
        $departmentProject = Project::factory()->create();
        $foreignProject = Project::factory()->create();
        $projectIds = collect([$teamProject->id, $departmentProject->id, $foreignProject->id]);

        $teamMember = User::factory()->create();
        $teamProject->users()->attach($teamMember);

        $departmentMember = User::factory()->create();
        $department = Department::factory()->create();
        $department->users()->attach($departmentMember);
        $departmentProject->departments()->attach($department);

        $outsider = User::factory()->create();
        $privileged = User::factory()->create();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value, $privileged);
        $privileged = $privileged->fresh();

        foreach ([$teamMember, $departmentMember, $outsider, $privileged] as $user) {
            $this->assertEqualsCanonicalizing(
                $this->legacyEnterableIds($user, $projectIds)->all(),
                $this->scopeEnterableIds($user, $projectIds)->all(),
                'Sichtbarkeit hat sich fuer Benutzer ' . $user->id . ' geaendert'
            );
        }
    }

    /** Die Uebersicht selbst zeigt weiterhin alle Projekte — nur der Einstieg ist gegated. */
    #[Test]
    public function the_overview_still_lists_projects_a_user_may_not_enter(): void
    {
        // entities_per_page ist in der Fabrik nicht gesetzt, der Controller erwartet aber eine Zahl.
        $user = $this->adminUser();
        $user->update(['entities_per_page' => 10]);
        Project::factory()->count(2)->create();

        $this->actingAs($user)->get(route('projects'))->assertOk();

        $outsider = User::factory()->create();

        $this->assertSame(0, Project::query()->visibleFor($outsider)->count());
        $this->assertSame(2, Project::query()->count());
    }
}
