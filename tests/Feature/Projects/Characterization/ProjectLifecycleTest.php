<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Jobs\SoftDeleteProjectJob;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung von Duplizieren, (Bulk-)Löschen, Wiederherstellen und endgültigem Löschen
 * von Projekten: Autorisierung (ProjectPolicy::delete, Team-Pivot delete_permission) und
 * DB-Effekt inkl. Job-Dispatch für die Lösch-Kaskade.
 */
final class ProjectLifecycleTest extends FeatureTestCase
{
    #[Test]
    public function duplicate_copies_team_pivot_flags_categories_and_creates_budget_table(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Faust']);
        $member = User::factory()->create();
        $project->users()->attach($member->id, [
            'access_budget' => true,
            'is_manager' => true,
            'can_write' => true,
            'delete_permission' => true,
        ]);
        $category = Category::factory()->create();
        $project->categories()->attach($category->id);

        $this->post(route('projects.duplicate', $project))->assertRedirect();

        $copy = Project::query()->where('name', __('(Copy)') . ' Faust')->sole();
        $this->assertDatabaseHas('project_user', [
            'project_id' => $copy->id,
            'user_id' => $member->id,
            'access_budget' => true,
            'is_manager' => true,
            'can_write' => true,
            'delete_permission' => true,
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $copy->id,
            'user_id' => $admin->id,
            'access_budget' => true,
        ]);
        $this->assertSame([$category->id], $copy->categories()->pluck('categories.id')->all());
        $this->assertNotNull($copy->table()->first());
    }

    #[Test]
    public function budget_access_member_can_duplicate_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create(['name' => 'Budgetprojekt']);
        $project->users()->attach($user->id, ['access_budget' => true]);

        $this->post(route('projects.duplicate', $project))->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => __('(Copy)') . ' Budgetprojekt']);
    }

    #[Test]
    public function plain_team_member_without_budget_or_manager_flag_cannot_duplicate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create(['name' => 'Nur Mitglied']);
        $project->users()->attach($user->id, ['can_write' => true]);

        $this->postJson(route('projects.duplicate', $project))->assertForbidden();

        $this->assertDatabaseMissing('projects', ['name' => __('(Copy)') . ' Nur Mitglied']);
    }

    #[Test]
    public function user_without_delete_right_cannot_destroy_project(): void
    {
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $project = Project::factory()->create();

        $this->delete(route('projects.destroy', $project))->assertForbidden();

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
        Bus::assertNotDispatched(SoftDeleteProjectJob::class);
    }

    #[Test]
    public function team_member_with_delete_permission_can_destroy_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['delete_permission' => true]);

        $this->delete(route('projects.destroy', $project))->assertRedirect(route('projects'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        Bus::assertDispatched(
            SoftDeleteProjectJob::class,
            fn (SoftDeleteProjectJob $job) => $this->jobProjectId($job) === $project->id
        );
    }

    #[Test]
    public function bulk_destroy_deletes_only_projects_the_user_may_delete(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $deletable = Project::factory()->create();
        $deletable->users()->attach($user->id, ['delete_permission' => true]);
        $foreign = Project::factory()->create();

        $this->delete(route('projects.bulk-destroy'), [
            'project_ids' => [$deletable->id, $foreign->id],
        ])->assertRedirect(route('projects'));

        $this->assertSoftDeleted('projects', ['id' => $deletable->id]);
        $this->assertNotSoftDeleted('projects', ['id' => $foreign->id]);
        Bus::assertDispatchedTimes(SoftDeleteProjectJob::class, 1);
    }

    #[Test]
    public function admin_bulk_destroy_soft_deletes_all_given_projects(): void
    {
        $this->actingAsAdmin();
        $projects = Project::factory()->count(2)->create();

        $this->delete(route('projects.bulk-destroy'), [
            'project_ids' => $projects->pluck('id')->all(),
        ])->assertRedirect();

        foreach ($projects as $project) {
            $this->assertSoftDeleted('projects', ['id' => $project->id]);
        }
        Bus::assertDispatchedTimes(SoftDeleteProjectJob::class, 2);
    }

    #[Test]
    public function bulk_destroy_validates_project_ids(): void
    {
        $this->actingAsAdmin();

        $this->deleteJson(route('projects.bulk-destroy'), ['project_ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_ids');
        $this->deleteJson(route('projects.bulk-destroy'), ['project_ids' => [999999999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_ids.0');
    }

    #[Test]
    public function admin_can_restore_trashed_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $project->delete();

        $this->patch(route('projects.restore', $project->id))->assertRedirect(route('projects.trashed'));

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function user_without_delete_right_cannot_restore_trashed_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $project->delete();

        $this->patch(route('projects.restore', $project->id))->assertForbidden();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function user_without_delete_right_cannot_force_delete_trashed_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $project->delete();

        $this->delete(route('projects.force', $project->id))->assertForbidden();

        Bus::assertNotDispatched(ForceDeleteProjectJob::class);
    }

    #[Test]
    public function user_without_delete_permission_cannot_force_delete_all_trashed_projects(): void
    {
        $this->actingAs(User::factory()->create());
        Project::factory()->create()->delete();

        $this->delete(route('projects.force.all'))->assertForbidden();

        Bus::assertNotDispatched(ForceDeleteProjectJob::class);
    }

    #[Test]
    public function restore_after_cascade_job_also_restores_budget_table(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        app(BudgetService::class)->generateBasicBudgetValues($project);
        $project->delete();
        app()->call([new SoftDeleteProjectJob($project->id, null), 'handle']);
        $this->assertSoftDeleted('tables', ['project_id' => $project->id]);

        $this->patch(route('projects.restore', $project->id))->assertRedirect(route('projects.trashed'));
        $this->assertNotSoftDeleted('tables', ['project_id' => $project->id]);
    }

    private function jobProjectId(object $job): ?int
    {
        foreach ((new \ReflectionObject($job))->getProperties() as $property) {
            if (str_contains(strtolower($property->getName()), 'project')) {
                $value = $property->getValue($job);

                return is_int($value) ? $value : null;
            }
        }

        return null;
    }
}
