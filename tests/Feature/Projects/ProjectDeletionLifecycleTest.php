<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Project\Jobs\SoftDeleteProjectJob;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\CauserResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Covers the full project trash lifecycle (soft delete -> restore -> force delete) for a
 * project whose events have shifts with assigned workers. The worker assignment is the case
 * that previously crashed the cascade because of the ShiftUser/ShiftWorker pivot mismatch.
 */
final class ProjectDeletionLifecycleTest extends FeatureTestCase
{
    /**
     * @return array{project: Project, event: Event, shift: Shift}
     */
    private function makeProjectWithAssignedShift(): array
    {
        $project = Project::factory()->create();
        $event = Event::factory()->create(['project_id' => $project->id]);
        $shift = Shift::factory()->create(['event_id' => $event->id]);

        // ShiftWorker is a MorphPivot (non auto-incrementing), so the created instance carries
        // no id; assertions below match on shift_id instead.
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => User::factory()->create()->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        return compact('project', 'event', 'shift');
    }

    private function softDelete(Project $project): void
    {
        app()->call([app(ProjectService::class), 'softDelete'], ['project' => $project]);
    }

    #[Test]
    public function soft_delete_cascades_to_events_shifts_and_workers(): void
    {
        ['project' => $project, 'event' => $event, 'shift' => $shift] =
            $this->makeProjectWithAssignedShift();

        $this->softDelete($project);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertSoftDeleted('shift_workers', ['shift_id' => $shift->id]);
    }

    #[Test]
    public function soft_delete_cascade_runs_after_project_row_was_already_deleted(): void
    {
        // Prod-Ablauf: Der Controller soft-deletet die Projekt-Zeile sofort, der Queue-Job
        // macht danach die Kaskade. Dabei ist $event->project bereits null (SoftDeletes-Scope) –
        // das hat die Kaskade früher beim ersten Event mit project_id gecrasht und
        // verwaiste Events hinterlassen (Sentry MOUSONTURM-P / MOUSONTURM-Q).
        ['project' => $project, 'event' => $event, 'shift' => $shift] =
            $this->makeProjectWithAssignedShift();

        $project->delete();

        app()->call([new SoftDeleteProjectJob($project->id), 'handle']);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertSoftDeleted('shift_workers', ['shift_id' => $shift->id]);
    }

    #[Test]
    public function cascade_job_leaves_a_project_alone_that_was_restored_in_the_meantime(): void
    {
        ['project' => $project, 'event' => $event, 'shift' => $shift] = $this->makeProjectWithAssignedShift();
        $causer = User::factory()->create();

        (new SoftDeleteProjectJob($project->id, $causer->id))->handle(app(ProjectService::class));

        $this->assertFalse($project->fresh()->trashed());
        $this->assertNotNull(Event::query()->find($event->id));
        $this->assertNotNull(Shift::query()->find($shift->id));
    }

    #[Test]
    public function cascade_job_does_not_leave_its_causer_for_later_jobs(): void
    {
        ['project' => $project] = $this->makeProjectWithAssignedShift();
        $causer = User::factory()->create();
        $project->delete();

        (new SoftDeleteProjectJob($project->id, $causer->id))->handle(app(ProjectService::class));

        $this->assertNull(CauserResolver::resolve());
    }

    #[Test]
    public function restore_brings_back_events_shifts_and_workers(): void
    {
        ['project' => $project, 'event' => $event, 'shift' => $shift] =
            $this->makeProjectWithAssignedShift();

        $this->softDelete($project);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
        $this->assertNotSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertNotSoftDeleted('shift_workers', ['shift_id' => $shift->id]);
    }

    #[Test]
    public function restore_brings_back_files_comments_checklist_tasks_and_categories(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $file = ProjectFile::factory()->create(['project_id' => $project->id]);
        $comment = Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $task = Task::factory()->create(['checklist_id' => $checklist->id]);
        $category = Category::factory()->create();
        $project->categories()->attach($category->id);

        $this->softDelete($project);

        $this->assertSoftDeleted('project_files', ['id' => $file->id]);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertSoftDeleted('checklists', ['id' => $checklist->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        $this->assertNotSoftDeleted('project_files', ['id' => $file->id]);
        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertNotSoftDeleted('checklists', ['id' => $checklist->id]);
        $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('category_project', [
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);
    }

    #[Test]
    public function restore_brings_back_contents_trashed_by_the_queued_cascade_after_the_project_row(): void
    {
        $project = Project::factory()->create();
        $comment = Comment::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $project->delete();
        $this->travel(20)->minutes();
        app()->call([new SoftDeleteProjectJob($project->id), 'handle']);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function restore_leaves_contents_in_the_trash_that_were_deleted_before_the_project(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $file = ProjectFile::factory()->create(['project_id' => $project->id]);
        $comment = Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $keptChecklist = Checklist::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $task = Task::factory()->create(['checklist_id' => $keptChecklist->id]);
        $keptTask = Task::factory()->create(['checklist_id' => $keptChecklist->id]);

        $file->delete();
        $comment->delete();
        $checklist->delete();
        $task->delete();

        $this->travel(1)->days();
        $this->softDelete($project);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        $this->assertSoftDeleted('project_files', ['id' => $file->id]);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertSoftDeleted('checklists', ['id' => $checklist->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertNotSoftDeleted('checklists', ['id' => $keptChecklist->id]);
        $this->assertNotSoftDeleted('tasks', ['id' => $keptTask->id]);
    }

    #[Test]
    public function restore_leaves_contents_in_the_trash_that_were_deleted_shortly_before_the_project(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $keptComment = Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $deletedComment = Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $deletedComment->delete();
        $this->travel(5)->minutes();
        $project->delete();
        $this->travel(20)->minutes();
        app()->call([new SoftDeleteProjectJob($project->id), 'handle']);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        $this->assertNotSoftDeleted('comments', ['id' => $keptComment->id]);
        $this->assertSoftDeleted('comments', ['id' => $deletedComment->id]);
    }

    #[Test]
    public function queued_cascade_skips_a_project_restored_before_the_job_ran(): void
    {
        ['project' => $project, 'event' => $event, 'shift' => $shift] =
            $this->makeProjectWithAssignedShift();

        $project->delete();
        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        app()->call([app(ProjectService::class), 'restore'], ['project' => $trashed]);

        app()->call([new SoftDeleteProjectJob($project->id), 'handle']);

        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
        $this->assertNotSoftDeleted('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function force_delete_removes_everything_permanently(): void
    {
        ['project' => $project, 'event' => $event, 'shift' => $shift] =
            $this->makeProjectWithAssignedShift();

        $this->softDelete($project);

        $trashed = Project::onlyTrashed()->findOrFail($project->id);
        $events = Event::onlyTrashed()->where('project_id', $project->id)->get();
        app()->call([app(EventService::class), 'forceDeleteAll'], ['events' => $events]);
        app()->call([app(ProjectService::class), 'forceDelete'], ['project' => $trashed]);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
        $this->assertDatabaseMissing('shift_workers', ['shift_id' => $shift->id]);
    }

    #[Test]
    public function force_delete_removes_the_shop_pictures_of_the_project(): void
    {
        Storage::fake();
        $project = Project::factory()->create();
        $production = TicketingProduction::query()->create(['project_id' => $project->id, 'hero_path' => 'cover.jpg']);
        $production->images()->create(['path' => 'further.jpg']);
        Storage::put('public/ticketing/cover.jpg', 'x');
        Storage::put('public/ticketing/further.jpg', 'x');
        Storage::put('public/ticketing/other-project.jpg', 'x');

        $this->softDelete($project);
        app()->call([app(ProjectService::class), 'forceDelete'], ['project' => Project::onlyTrashed()->findOrFail($project->id)]);

        Storage::assertMissing('public/ticketing/cover.jpg');
        Storage::assertMissing('public/ticketing/further.jpg');
        Storage::assertExists('public/ticketing/other-project.jpg');
        $this->assertDatabaseMissing('ticketing_productions', ['project_id' => $project->id]);
    }
}
