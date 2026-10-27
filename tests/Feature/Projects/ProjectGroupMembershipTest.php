<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Gruppenzugehörigkeit beim Speichern der Basisdaten: Aufrufer ohne geladene Unterprojekte
 * (Projektübersicht, Basisdaten-Komponente) dürfen die Gruppe nicht leeren.
 */
final class ProjectGroupMembershipTest extends FeatureTestCase
{
    #[Test]
    public function saving_group_without_projects_key_keeps_its_projects(): void
    {
        $this->actingAsAdmin();
        [$group, $children] = $this->groupWithChildren(2);

        $this->patch(route('projects.update', $group), ['name' => 'Festival', 'isGroup' => true])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing($children->pluck('id')->all(), $this->childIds($group));
    }

    #[Test]
    public function saving_group_with_null_projects_keeps_its_projects(): void
    {
        $this->actingAsAdmin();
        [$group, $children] = $this->groupWithChildren(2);

        $this->patch(route('projects.update', $group), ['name' => 'Festival', 'isGroup' => true, 'projects' => null])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing($children->pluck('id')->all(), $this->childIds($group));
    }

    #[Test]
    public function saving_group_with_explicit_projects_replaces_them(): void
    {
        $this->actingAsAdmin();
        [$group, $children] = $this->groupWithChildren(2);
        $newChild = Project::factory()->create();

        $this->patch(route('projects.update', $group), [
            'name' => 'Festival',
            'isGroup' => true,
            'projects' => [$children->first()->id, $newChild->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$children->first()->id, $newChild->id], $this->childIds($group));
    }

    #[Test]
    public function group_cannot_contain_itself(): void
    {
        $this->actingAsAdmin();
        [$group] = $this->groupWithChildren(0);

        $this->patch(route('projects.update', $group), [
            'name' => 'Festival',
            'isGroup' => true,
            'projects' => [$group->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame([], $this->childIds($group));
    }

    #[Test]
    public function adding_an_invisible_project_to_a_group_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$group] = $this->groupWithChildren(0);
        $group->users()->attach($user->id, ['can_write' => true]);
        $foreign = Project::factory()->create();

        $this->patch(route('projects.update', $group), [
            'name' => 'Festival',
            'isGroup' => true,
            'projects' => [$foreign->id],
        ])->assertForbidden();

        $this->assertSame([], $this->childIds($group));
    }

    #[Test]
    public function existing_invisible_children_do_not_block_saving_the_group(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$group, $children] = $this->groupWithChildren(1);
        $group->users()->attach($user->id, ['can_write' => true]);

        $this->patch(route('projects.update', $group), [
            'name' => 'Festival neu',
            'isGroup' => true,
            'projects' => $children->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Festival neu', $group->fresh()->name);
        $this->assertSame($children->pluck('id')->all(), $this->childIds($group));
    }

    #[Test]
    public function selecting_a_normal_project_as_group_does_not_turn_it_into_a_group(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $notAGroup = Project::factory()->create(['is_group' => false]);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'selectedGroup' => ['id' => $notAGroup->id],
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $notAGroup->fresh()->is_group);
        $this->assertSame([], $this->childIds($notAGroup));
    }

    #[Test]
    public function selecting_an_invisible_group_is_forbidden_but_keeping_the_current_one_works(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        [$currentGroup] = $this->groupWithChildren(0);
        $currentGroup->projectsOfGroup()->attach($project->id);
        [$foreignGroup] = $this->groupWithChildren(0);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'selectedGroup' => ['id' => $currentGroup->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame([$project->id], $this->childIds($currentGroup));

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'selectedGroup' => ['id' => $foreignGroup->id],
        ])->assertForbidden();
        $this->assertSame([], $this->childIds($foreignGroup));
    }

    #[Test]
    public function storing_a_project_into_a_normal_project_does_not_turn_it_into_a_group(): void
    {
        $this->actingAsAdmin();
        $notAGroup = Project::factory()->create(['is_group' => false]);

        $this->post(route('projects.store'), [
            'name' => 'Neues Stück',
            'selectedGroup' => ['id' => $notAGroup->id],
        ]);

        $this->assertFalse((bool) $notAGroup->fresh()->is_group);
        $this->assertSame([], $this->childIds($notAGroup));
    }

    #[Test]
    public function group_project_names_are_listed_sorted_for_viewers(): void
    {
        $this->actingAsAdmin();
        $group = Project::factory()->create(['is_group' => true]);
        $zeta = Project::factory()->create(['name' => 'Zeta']);
        $alpha = Project::factory()->create(['name' => 'Alpha']);
        $group->projectsOfGroup()->attach([$zeta->id, $alpha->id]);

        $this->getJson(route('project.groupProjectNames', $group))
            ->assertOk()
            ->assertExactJson([
                ['id' => $alpha->id, 'name' => 'Alpha'],
                ['id' => $zeta->id, 'name' => 'Zeta'],
            ]);
    }

    #[Test]
    public function group_project_names_require_view_right(): void
    {
        $this->actingAs(User::factory()->create());
        $group = Project::factory()->create(['is_group' => true]);

        $this->getJson(route('project.groupProjectNames', $group))->assertForbidden();
    }

    /**
     * @return array{0: Project, 1: \Illuminate\Support\Collection<int, Project>}
     */
    private function groupWithChildren(int $count): array
    {
        $group = Project::factory()->create(['name' => 'Festival', 'is_group' => true]);
        $children = Project::factory()->count($count)->create();
        $group->projectsOfGroup()->attach($children->pluck('id')->all());

        return [$group, $children];
    }

    /**
     * @return array<int, int>
     */
    private function childIds(Project $group): array
    {
        return $group->projectsOfGroup()->pluck('projects.id')->map(fn ($id): int => (int) $id)->all();
    }
}
