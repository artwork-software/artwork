<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Jobs\SoftDeleteProjectJob;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectDeletionControllerTest extends FeatureTestCase
{
    #[Test]
    public function destroy_soft_deletes_the_project_immediately_and_offloads_the_cascade(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        Event::factory()->count(3)->create(['project_id' => $project->id]);

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect();

        // The project row vanishes from the overview right away (synchronous), ...
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        // ... while the heavy cascade is handed to the job instead of running inline.
        Bus::assertDispatched(
            SoftDeleteProjectJob::class,
            fn (SoftDeleteProjectJob $job) => $this->jobTargetsProject($job, $project->id)
        );
    }

    #[Test]
    public function destroy_sends_one_notification_to_members_but_not_to_the_deleter(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->users()->attach([
            $admin->id => ['access_budget' => false, 'is_manager' => false, 'can_write' => false, 'delete_permission' => false],
            $member->id => ['access_budget' => false, 'is_manager' => false, 'can_write' => false, 'delete_permission' => false],
        ]);
        Event::factory()->count(3)->create(['project_id' => $project->id]);

        $this->delete(route('projects.destroy', $project))->assertRedirect();

        // Other members get exactly the one consolidated notification...
        Notification::assertSentTo($member, ProjectNotification::class);
        // ...but the person who triggered the deletion is excluded (framework self-exclusion).
        Notification::assertNotSentTo($admin, ProjectNotification::class);
    }

    #[Test]
    public function force_delete_offloads_to_a_queued_job(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $project->delete();

        $this->delete(route('projects.force', ['id' => $project->id]))
            ->assertRedirect(route('projects.trashed'));

        Bus::assertDispatched(
            ForceDeleteProjectJob::class,
            fn (ForceDeleteProjectJob $job) => $this->jobTargetsProject($job, $project->id)
        );
    }

    private function jobTargetsProject(object $job, int $projectId): bool
    {
        $reflection = new \ReflectionProperty($job, 'projectId');
        $reflection->setAccessible(true);

        return $reflection->getValue($job) === $projectId;
    }
}
