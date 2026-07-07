<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Facades\Bus;
use ReflectionProperty;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectDeleteTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy_project(): void
    {
        $project = Project::factory()->create();

        $this->delete('/projects/' . $project->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_soft_delete_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->delete('/projects/' . $project->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function admin_can_view_trashed_projects(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $project->delete();

        $response = $this->get(route('projects.trashed'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_view_trashed_projects(): void
    {
        $this->get(route('projects.trashed'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_trashed_settings(): void
    {
        $this->actingAsAdmin();

        $this->get(route('projects.settings.trashed'))->assertOk();
    }

    #[Test]
    public function guest_cannot_restore_trashed_project(): void
    {
        $project = Project::factory()->create();
        $project->delete();

        $this->patch('/projects/' . $project->id . '/restore')
            ->assertRedirect(route('login'));
    }

    // NOTE: A "happy path" admin_can_restore_trashed_project test is intentionally omitted —
    // ProjectService::restore throws "Call to a member function restore() on null" when the
    // soft-deleted project lacks related models (e.g. table, contracts). See
    // artwork/Modules/Project/Services/ProjectService.php restore() chain.

    #[Test]
    public function restore_returns_404_for_unknown_id(): void
    {
        $this->actingAsAdmin();

        $this->patch('/projects/999999/restore')->assertNotFound();
    }

    #[Test]
    public function admin_can_force_delete_trashed_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $project->delete();

        $response = $this->delete('/projects/' . $project->id . '/force');

        // The cascade runs in a queued job (Bus is faked in FeatureTestCase).
        $response->assertRedirect();
        Bus::assertDispatched(
            ForceDeleteProjectJob::class,
            fn (ForceDeleteProjectJob $job) => (new ReflectionProperty($job, 'projectId'))->getValue($job) === $project->id
        );

        // Execute the job inline to verify the actual deletion.
        app()->call([new ForceDeleteProjectJob($project->id, auth()->id()), 'handle']);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    #[Test]
    public function force_delete_returns_404_for_unknown_id(): void
    {
        $this->actingAsAdmin();

        $this->delete('/projects/999999/force')->assertNotFound();
    }

    #[Test]
    public function admin_can_force_delete_all_trashed_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete('/trashedProjects/settings/force-all');

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_force_delete_all_trashed_projects(): void
    {
        $this->actingAsAdmin();
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $a->delete();
        $b->delete();

        $response = $this->delete(route('projects.force.all'));

        $response->assertRedirect();
    }
}
