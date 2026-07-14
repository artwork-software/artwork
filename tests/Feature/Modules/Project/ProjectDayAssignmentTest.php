<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projektzuordnung im Dienstplan (project_day_assignments):
 * CRUD + Rechte, Ganz-Zeitraum, Wunsch-Regeln, Verwandeln/Restore,
 * Frei-Auflösung und Re-Materialisierung bei Terminverschiebung.
 */
final class ProjectDayAssignmentTest extends FeatureTestCase
{
    private function createProjectWithPeriod(string $start, string $end): Project
    {
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();

        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $eventType->id,
            'start_time' => $start . ' 10:00:00',
            'end_time' => $end . ' 18:00:00',
        ]);

        return $project;
    }

    private function service(): ProjectDayAssignmentService
    {
        return app(ProjectDayAssignmentService::class);
    }

    // ---------- Anlegen + Rechte ----------

    #[Test]
    public function planner_can_create_binding_single_day_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $response = $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'binding',
            'full_period' => false,
            'days' => ['2026-08-02', '2026-08-03'],
        ]);

        $response->assertOk()->assertJson(['created' => 2, 'skipped' => 0]);

        $rows = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame(1, $rows->pluck('group_id')->unique()->count());
        $this->assertFalse($rows->first()->is_full_period);

        // Auto-Add ins Projektteam
        $this->assertTrue($project->users()->where('users.id', $worker->id)->exists());
    }

    #[Test]
    public function non_planner_cannot_create_binding_assignment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'binding',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertForbidden();
    }

    #[Test]
    public function wish_can_only_be_created_for_oneself(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertOk();

        // Auch Planer*innen dürfen Wünsche nicht für andere anlegen
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-03'],
        ])->assertForbidden();

        // Wünsche landen NICHT im Projektteam
        $this->assertFalse($project->users()->where('users.id', $user->id)->exists());
    }

    #[Test]
    public function full_period_assignment_materializes_all_days_of_project_period(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create();

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'binding',
            'full_period' => true,
            'days' => [],
        ])->assertOk();

        $rows = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(5, $rows);
        $this->assertTrue($rows->every(static fn ($row) => $row->is_full_period));
        $this->assertSame('2026-08-01', $rows->first()->date->format('Y-m-d'));
        $this->assertSame('2026-08-05', $rows->last()->date->format('Y-m-d'));
    }

    #[Test]
    public function duplicate_days_are_skipped_on_creation(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );

        $created = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        );

        $this->assertCount(1, $created);
        $this->assertSame('2026-08-03', $created->first()->date->format('Y-m-d'));
    }

    #[Test]
    public function binding_assignment_absorbs_existing_wish_on_same_day(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $wish = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-02'],
            false
        )->first();

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );

        $this->assertSoftDeleted('project_day_assignments', ['id' => $wish->id]);
        $this->assertSame(
            1,
            ProjectDayAssignment::query()
                ->where('project_id', $project->id)
                ->forEmployable(User::class, $worker->id)
                ->count()
        );
    }

    #[Test]
    public function wish_on_absence_day_returns_friendly_validation_error(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => true,
            'is_series' => false,
            'comment' => 'OFF_WORK',
            'type' => 'OFF_WORK',
            'created_by' => $user->id,
        ]);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['days']);
    }

    // ---------- Frei-/Abwesenheits-Auflösung ----------

    #[Test]
    public function free_work_entry_dissolves_binding_assignment(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $this->service()->handleVacationEntry($worker, ['2026-08-02'], 'FREE_WORK');

        $this->assertSoftDeleted('project_day_assignments', ['id' => $assignment->id]);
    }

    #[Test]
    public function absence_dissolves_wish_but_keeps_binding_assignment(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $binding = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $wish = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-03'],
            false
        )->first();

        $this->service()->handleVacationEntry($worker, ['2026-08-02', '2026-08-03'], 'OFF_WORK');

        $this->assertSoftDeleted('project_day_assignments', ['id' => $wish->id]);
        $this->assertDatabaseHas('project_day_assignments', ['id' => $binding->id, 'deleted_at' => null]);
    }

    // ---------- Verwandeln + Auto-Restore ----------

    #[Test]
    public function shift_assignment_supersedes_assignment_and_removal_restores_it(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $shift = Shift::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);

        $this->service()->supersedeForShiftAssignment($shift, User::class, $worker->id);

        $this->assertSoftDeleted('project_day_assignments', ['id' => $assignment->id]);
        $this->assertSame(
            $shift->id,
            ProjectDayAssignment::withTrashed()->find($assignment->id)->superseded_by_shift_id
        );

        $this->service()->restoreForShiftRemoval($shift, User::class, $worker->id);

        $restored = ProjectDayAssignment::find($assignment->id);
        $this->assertNotNull($restored);
        $this->assertNull($restored->superseded_by_shift_id);
    }

    #[Test]
    public function shift_of_other_project_does_not_supersede_assignment(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $otherProject = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $shift = Shift::factory()->create([
            'project_id' => $otherProject->id,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);

        $this->service()->supersedeForShiftAssignment($shift, User::class, $worker->id);

        $this->assertDatabaseHas('project_day_assignments', ['id' => $assignment->id, 'deleted_at' => null]);
    }

    #[Test]
    public function binding_creation_skips_days_already_covered_by_shift_of_same_project(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $shift = Shift::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);
        \Artwork\Modules\Shift\Models\ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => \Artwork\Modules\Shift\Models\ShiftQualification::factory()->create()->id,
        ]);

        $created = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        );

        $this->assertCount(1, $created);
        $this->assertSame('2026-08-03', $created->first()->date->format('Y-m-d'));
    }

    // ---------- Terminverschiebung ----------

    #[Test]
    public function rescheduling_an_event_shrinks_full_period_groups_and_dissolves_out_of_period_singles(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create();

        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        $singleDay = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-05'],
            false
        )->first();

        // Projekt verschiebt sich: Termin endet jetzt am 03.08. statt 05.08.
        // (ProjectDayAssignmentEventObserver re-materialisiert automatisch)
        $project->events()->first()->update([
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-03 18:00:00',
        ]);

        $fullPeriodDates = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->where('is_full_period', true)
            ->orderBy('date')
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        $this->assertSame(['2026-08-01', '2026-08-02', '2026-08-03'], $fullPeriodDates->all());
        $this->assertSoftDeleted('project_day_assignments', ['id' => $singleDay->id]);
    }

    #[Test]
    public function creating_an_event_extends_full_period_groups(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create();

        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        // Neuer Termin verlängert den Projektzeitraum bis 05.08.
        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $project->events()->first()->event_type_id,
            'start_time' => '2026-08-04 10:00:00',
            'end_time' => '2026-08-05 18:00:00',
        ]);

        $this->assertSame(
            5,
            ProjectDayAssignment::query()
                ->where('project_id', $project->id)
                ->forEmployable(User::class, $worker->id)
                ->where('is_full_period', true)
                ->count()
        );
    }

    #[Test]
    public function deleting_an_event_shrinks_full_period_groups(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $extendingEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $project->events()->first()->event_type_id,
            'start_time' => '2026-08-04 10:00:00',
            'end_time' => '2026-08-05 18:00:00',
        ]);
        $worker = User::factory()->create();

        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        $this->assertSame(5, ProjectDayAssignment::query()->where('project_id', $project->id)->count());

        $extendingEvent->delete();

        $remainingDates = ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->orderBy('date')
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        $this->assertSame(['2026-08-01', '2026-08-02', '2026-08-03'], $remainingDates->all());
    }

    // ---------- Wunsch annehmen + Löschen ----------

    #[Test]
    public function planner_can_accept_wish_group_as_binding(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-02', '2026-08-03'],
        ])->assertOk();

        $wish = ProjectDayAssignment::query()->forEmployable(User::class, $user->id)->first();

        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->patchJson(route('project-day-assignments.accept-wish', ['projectDayAssignment' => $wish->id]))
            ->assertOk();

        $types = ProjectDayAssignment::query()
            ->where('group_id', $wish->group_id)
            ->pluck('type')
            ->unique();

        $this->assertSame(['binding'], $types->all());
        $this->assertTrue($project->users()->where('users.id', $user->id)->exists());
    }

    #[Test]
    public function delete_endpoint_removes_single_day_or_whole_group(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $rows = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03', '2026-08-04'],
            false
        );

        // Einzeltag löschen
        $this->deleteJson(route('project-day-assignments.destroy', [
            'projectDayAssignment' => $rows->first()->id,
        ]))->assertOk();

        $this->assertSame(
            2,
            ProjectDayAssignment::query()->where('group_id', $rows->first()->group_id)->count()
        );

        // Ganze Gruppe löschen
        $this->deleteJson(route('project-day-assignments.destroy', [
            'projectDayAssignment' => $rows->last()->id,
            'whole_group' => 1,
        ]))->assertOk();

        $this->assertSame(
            0,
            ProjectDayAssignment::query()->where('group_id', $rows->first()->group_id)->count()
        );
    }

    #[Test]
    public function non_planner_cannot_delete_binding_assignment(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $this->actingAs($worker);

        $this->deleteJson(route('project-day-assignments.destroy', [
            'projectDayAssignment' => $assignment->id,
        ]))->assertForbidden();
    }

    // ---------- Vorschläge + Multiedit ----------

    #[Test]
    public function project_options_flag_projects_covering_all_days(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $covering = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $this->createProjectWithPeriod('2026-09-01', '2026-09-10');

        $response = $this->getJson(route('project-day-assignments.projects', [
            'days' => ['2026-08-02', '2026-08-03'],
        ]))->assertOk();

        $projects = collect($response->json('projects'));
        $coveringEntry = $projects->firstWhere('id', $covering->id);

        $this->assertNotNull($coveringEntry);
        $this->assertTrue($coveringEntry['covers_all_days']);
        $this->assertNull($projects->firstWhere('id', 999999));
    }

    #[Test]
    public function update_user_cell_creates_binding_assignments_for_selected_days(): void
    {
        $this->actingAsUserWith([PermissionEnum::SHIFT_PLANNER->value]);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create();

        $this->postJson(route('shift.plan.user.cell.update'), [
            'comment' => null,
            'vacation_type' => ['name' => 'Keine Änderung', 'type' => null],
            'entities' => [
                [
                    'id' => $worker->id,
                    'type' => 0,
                    'days' => ['2026-08-02', '2026-08-03'],
                ],
            ],
            'individual_times' => [],
            'project_id' => $project->id,
        ])->assertSuccessful();

        $this->assertSame(
            2,
            ProjectDayAssignment::query()
                ->where('project_id', $project->id)
                ->forEmployable(User::class, $worker->id)
                ->binding()
                ->count()
        );
    }

    #[Test]
    public function reschedule_impact_endpoint_lists_out_of_period_single_day_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create();

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-05'],
            false
        );

        $event = $project->events()->first();

        // Termin soll auf 01.-03.08. verkürzt werden -> Zuordnung am 05.08. fällt raus
        $response = $this->getJson(route('events.project-assignment-impact', [
            'event' => $event->id,
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-03 18:00:00',
        ]))->assertOk();

        $affected = $response->json('affected');

        $this->assertCount(1, $affected);
        $this->assertSame(['05.08.2026'], $affected[0]['dates']);
    }
}
