<?php

namespace Tests\Feature\Modules\Project;

use App\Settings\ShiftSettings;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Globaler Schalter „Projektzuordnungen“ in den Schichteinstellungen
 * (shift-settings.project_assignments_enabled).
 */
final class ProjectAssignmentsToggleTest extends FeatureTestCase
{
    private function createProjectWithPeriod(string $start, string $end): Project
    {
        $project = Project::factory()->create();

        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => $start . ' 10:00:00',
            'end_time' => $end . ' 18:00:00',
        ]);

        return $project;
    }

    private function setEnabled(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->project_assignments_enabled = $enabled;
        $settings->save();
    }

    #[Test]
    public function setting_is_enabled_by_default_and_shared_with_frontend(): void
    {
        $this->actingAsAdmin();

        $this->assertTrue(ProjectDayAssignmentService::isEnabled());

        $this->get(route('shift.settings'))
            ->assertInertia(fn ($page) => $page->where('project_assignments_enabled', true));
    }

    #[Test]
    public function admin_can_toggle_setting(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('shift.settings.update.project-assignments-enabled'), [
            'project_assignments_enabled' => false,
        ])->assertRedirect();

        $this->assertFalse(app(ShiftSettings::class)->refresh()->project_assignments_enabled);
    }

    #[Test]
    public function user_without_shift_settings_access_cannot_toggle_setting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('shift.settings.update.project-assignments-enabled'), [
            'project_assignments_enabled' => false,
        ])->assertForbidden();

        $this->assertTrue(app(ShiftSettings::class)->refresh()->project_assignments_enabled);
    }

    #[Test]
    public function no_assignments_or_wishes_can_be_created_when_disabled(): void
    {
        $this->setEnabled(false);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $this->actingAs($worker);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertForbidden();

        $this->assertSame(0, ProjectDayAssignment::query()->count());
    }

    #[Test]
    public function wishes_cannot_be_accepted_when_disabled(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $this->actingAs($worker);
        $wish = app(ProjectDayAssignmentService::class)->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-02'],
            false
        )->firstOrFail();

        $this->setEnabled(false);
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->patchJson(route('project-day-assignments.accept-wish', ['projectDayAssignment' => $wish->id]))
            ->assertForbidden();

        $this->assertSame(ProjectDayAssignmentType::WISH->value, $wish->refresh()->type);
    }

    #[Test]
    public function prechecks_report_nothing_when_disabled(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        app(ProjectDayAssignmentService::class)->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );
        $shift = Shift::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);

        $this->setEnabled(false);

        $this->getJson(route('shifts.project-assignees', ['shift' => $shift->id]))
            ->assertOk()
            ->assertExactJson(['assignees' => []]);

        $this->postJson(route('project-day-assignments.vacation-impact'), [
            'workers' => [['type' => 0, 'id' => $worker->id]],
            'dates' => ['2026-08-02'],
            'vacation_type' => 'FREE_WORK',
        ])->assertOk()->assertExactJson(['affected' => []]);
    }

    #[Test]
    public function person_is_not_notified_about_binding_changes_when_disabled(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $assignment = app(ProjectDayAssignmentService::class)->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->firstOrFail();
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();
        Notification::fake();

        $this->setEnabled(false);
        app(ProjectDayAssignmentService::class)->deleteAssignment($assignment, true);
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

        Notification::assertNothingSent();
        $this->assertNull(ProjectDayAssignment::query()->find($assignment->id));
    }

    #[Test]
    public function existing_assignments_are_hidden_but_kept_when_disabled(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $service = app(ProjectDayAssignmentService::class);
        $service->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );
        $start = \Carbon\Carbon::parse('2026-08-01');
        $end = \Carbon\Carbon::parse('2026-08-10');

        $this->setEnabled(false);

        $this->assertTrue($service->getAssignmentsGroupedByDate(User::class, [$worker->id], $start, $end)->isEmpty());
        $this->getJson(route('projects.day-assignments', ['project' => $project->id]))
            ->assertOk()
            ->assertExactJson(['assignments' => []]);
        $this->assertSame(1, ProjectDayAssignment::query()->count());

        $this->setEnabled(true);

        $this->assertTrue($service->getAssignmentsGroupedByDate(User::class, [$worker->id], $start, $end)->has($worker->id));
    }
}
