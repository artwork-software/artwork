<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
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
}
