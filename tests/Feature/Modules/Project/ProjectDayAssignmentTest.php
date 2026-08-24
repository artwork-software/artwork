<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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

    /**
     * Nach der Response aufgeschobene Arbeit (defer(): Personen-/Planer-Notifications,
     * Broadcasts) sofort ausführen — bei HTTP-Tests übernimmt das der Kernel-Terminate,
     * bei direkten Service-Aufrufen muss manuell geflusht werden.
     */
    private function flushDeferred(): void
    {
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();
    }

    // ---------- Anlegen + Rechte ----------

    #[Test]
    public function planner_can_create_binding_single_day_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $user = User::factory()->create(['can_work_shifts' => true]);
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
        $user = User::factory()->create(['can_work_shifts' => true]);
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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $user = User::factory()->create(['can_work_shifts' => true]);
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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
    public function deleted_group_is_not_restored_after_shift_removal(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $created = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        );

        $shift = Shift::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);
        $this->service()->supersedeForShiftAssignment($shift, User::class, $worker->id);

        $supersededRow = $created->first(fn ($row) => $row->date->format('Y-m-d') === '2026-08-02');
        $activeRow = $created->first(fn ($row) => $row->date->format('Y-m-d') === '2026-08-03');

        // Planer entfernt die gesamte Zuordnung — danach darf der Schicht-Entzug
        // den supersedeten Tag nicht wieder herstellen (Zombie-Restore)
        $this->service()->deleteAssignment($activeRow->fresh(), true);
        $this->service()->restoreForShiftRemoval($shift, User::class, $worker->id);

        $this->assertSoftDeleted('project_day_assignments', ['id' => $supersededRow->id]);
    }

    #[Test]
    public function restore_is_skipped_on_free_day(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

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

        // Person trägt danach "Frei" ein — der spätere Schicht-Entzug darf die
        // verbindliche Zuordnung auf dem Frei-Tag nicht restaurieren
        $worker->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => true,
            'type' => 'FREE_WORK',
            'comment' => 'FREE_WORK',
            'is_series' => false,
            'created_by' => $worker->id,
        ]);

        $this->service()->restoreForShiftRemoval($shift, User::class, $worker->id);

        $this->assertSoftDeleted('project_day_assignments', ['id' => $assignment->id]);
        $this->assertNull(
            ProjectDayAssignment::withTrashed()->find($assignment->id)->superseded_by_shift_id
        );
    }

    #[Test]
    public function removal_of_one_of_two_covering_shifts_repoints_the_superseded_reference(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        )->first();

        $qualificationId = \Artwork\Modules\Shift\Models\ShiftQualification::factory()->create()->id;
        $makeShift = function () use ($project, $worker, $qualificationId) {
            $shift = Shift::factory()->create([
                'project_id' => $project->id,
                'start_date' => '2026-08-02',
                'end_date' => '2026-08-02',
            ]);
            \Artwork\Modules\Shift\Models\ShiftWorker::create([
                'shift_id' => $shift->id,
                'employable_type' => User::class,
                'employable_id' => $worker->id,
                'shift_qualification_id' => $qualificationId,
            ]);

            return $shift;
        };

        $shiftA = $makeShift();
        $this->service()->supersedeForShiftAssignment($shiftA, User::class, $worker->id);
        $shiftB = $makeShift();

        // Schicht A wird entfernt, B deckt den Tag weiter ab: der Verweis muss auf B
        // umgehängt werden, damit der spätere Entzug von B die Zuordnung restauriert
        \Artwork\Modules\Shift\Models\ShiftWorker::query()
            ->where('shift_id', $shiftA->id)
            ->where('employable_id', $worker->id)
            ->forceDelete();
        $this->service()->restoreForShiftRemoval($shiftA, User::class, $worker->id);

        $this->assertSoftDeleted('project_day_assignments', ['id' => $assignment->id]);
        $this->assertSame(
            $shiftB->id,
            ProjectDayAssignment::withTrashed()->find($assignment->id)->superseded_by_shift_id
        );

        \Artwork\Modules\Shift\Models\ShiftWorker::query()
            ->where('shift_id', $shiftB->id)
            ->where('employable_id', $worker->id)
            ->forceDelete();
        $this->service()->restoreForShiftRemoval($shiftB, User::class, $worker->id);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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

    #[Test]
    public function deleting_the_last_event_dissolves_all_project_assignments(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        $project->events()->firstOrFail()->delete();

        $this->assertSame(
            0,
            ProjectDayAssignment::query()->where('project_id', $project->id)->count()
        );
    }

    // ---------- Wunsch annehmen + Löschen ----------

    #[Test]
    public function planner_can_accept_wish_group_as_binding(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $user = User::factory()->create(['can_work_shifts' => true]);
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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $worker = User::factory()->create(['can_work_shifts' => true]);

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
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::VIEW_SHIFT_PLAN->value,
        ]);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create(['can_work_shifts' => true]);

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

    #[Test]
    public function reschedule_impact_ignores_assignments_that_are_already_out_of_period(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::VIEW_SHIFT_PLAN->value,
        ]);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        // Zuordnung liegt schon jetzt ausserhalb des Projektzeitraums
        ProjectDayAssignment::query()->create([
            'project_id' => $project->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'date' => '2026-08-09',
            'type' => ProjectDayAssignmentType::BINDING,
            'is_full_period' => false,
            'group_id' => (string) Str::uuid(),
        ]);

        $event = $project->events()->firstOrFail();

        // Unveraenderte Zeiten (z. B. Speichern nach reiner Beschreibungsaenderung)
        $response = $this->getJson(route('events.project-assignment-impact', [
            'event' => $event->id,
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-05 18:00:00',
        ]))->assertOk();

        $this->assertSame([], $response->json('affected'));
    }

    #[Test]
    public function reschedule_impact_requires_shift_plan_view_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');

        $this->getJson(route('events.project-assignment-impact', [
            'event' => $project->events()->firstOrFail()->id,
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-03 18:00:00',
        ]))->assertForbidden();
    }

    #[Test]
    public function shift_multi_edit_validates_full_period_assignments_and_worker(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->postJson(route('shift.multi.edit.save'), [
            'userType' => 0,
            'userTypeId' => $worker->id,
            'fullPeriodProjectAssignments' => [$project->id],
        ])->assertSuccessful();

        $this->assertSame(
            3,
            ProjectDayAssignment::query()
                ->where('project_id', $project->id)
                ->forEmployable(User::class, $worker->id)
                ->count()
        );

        $this->postJson(route('shift.multi.edit.save'), [
            'userType' => 99,
            'userTypeId' => PHP_INT_MAX,
            'fullPeriodProjectAssignments' => [PHP_INT_MAX],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'userType',
            'fullPeriodProjectAssignments.0',
        ]);
    }

    #[Test]
    public function full_period_lookup_and_delete_are_independent_of_the_visible_date_range(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $created = $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        $groupId = $created->firstOrFail()->group_id;

        $this->getJson(route('project-day-assignments.full-period.index', [
            'worker_type' => 0,
            'worker_id' => $worker->id,
        ]))->assertOk()->assertJsonPath('assignments.0', [
            'project_id' => $project->id,
            'group_id' => $groupId,
        ]);

        $this->deleteJson(route('project-day-assignments.full-period.destroy', [
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'group_id' => $groupId,
        ]))->assertOk();

        $this->assertSame(
            0,
            ProjectDayAssignment::withTrashed()->where('group_id', $groupId)->count()
        );
    }

    #[Test]
    public function moving_an_event_to_another_project_checks_the_old_project_period(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::VIEW_SHIFT_PLAN->value,
        ]);
        $oldProject = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $newProject = $this->createProjectWithPeriod('2026-09-01', '2026-09-02');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $this->service()->createAssignments(
            $oldProject,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-05'],
            false
        );

        $this->getJson(route('events.project-assignment-impact', [
            'event' => $oldProject->events()->firstOrFail()->id,
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-02 18:00:00',
            'project_id' => $newProject->id,
        ]))->assertOk()->assertJsonCount(1, 'affected');
    }

    #[Test]
    public function accepting_a_wish_force_deletes_rows_absorbed_by_existing_bindings(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );
        $wish = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-02'],
            false
        )->firstOrFail();

        $this->service()->acceptWishGroup($wish);

        $this->assertNull(ProjectDayAssignment::withTrashed()->find($wish->id));
    }

    // ---------- Personen-Benachrichtigung (gebündelt) ----------

    #[Test]
    public function person_is_notified_when_bindingly_assigned(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        );
        $this->flushDeferred();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            static function (ShiftNotification $notification) use ($project, $worker): bool {
                $data = $notification->toArray();

                return $data->notificationKey === sprintf(
                        'project-assignment-assigned-%d-%s',
                        $worker->id,
                        Carbon::now()->format('Y-m-d')
                    )
                    && str_contains($data->title, $project->name);
            }
        );
    }

    #[Test]
    public function same_day_person_notifications_are_bundled_into_one(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        // Bereits vorhandene ungelesene Benachrichtigung desselben Tages simulieren
        // (Notification::fake() im FeatureTestCase schreibt selbst keine DB-Zeilen)
        $notificationKey = sprintf(
            'project-assignment-assigned-%d-%s',
            $worker->id,
            Carbon::now()->format('Y-m-d')
        );
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => ShiftNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $worker->id,
            'data' => [
                'title' => 'Neue Projektzuordnung: Erstes Projekt',
                'description' => [['type' => 'string', 'title' => 'Erstes Projekt · 01.08.2026', 'href' => null]],
                'notificationKey' => $notificationKey,
            ],
            'read_at' => null,
        ]);

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );
        $this->flushDeferred();

        // Bestehende Benachrichtigung wurde gebündelt statt eine zweite zu erzeugen
        Notification::assertNotSentTo($worker, ShiftNotification::class);

        $data = DatabaseNotification::query()
            ->where('notifiable_id', $worker->id)
            ->firstOrFail()
            ->data;

        $this->assertSame(2, $data['bundleCount']);
        $this->assertSame(
            __('notification.project_assignment.person_assigned_bundled', ['count' => 2], $worker->refresh()->language),
            $data['title']
        );
        $this->assertCount(2, $data['description']);
        $this->assertStringContainsString($project->name, $data['description'][1]['title']);
    }

    #[Test]
    public function no_person_notification_for_wishes_or_self_assignments(): void
    {
        $admin = $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-02'],
            false
        );
        $this->service()->createAssignments(
            $project,
            User::class,
            $admin->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );
        $this->flushDeferred();

        Notification::assertNotSentTo($worker, ShiftNotification::class);
        Notification::assertNotSentTo($admin, ShiftNotification::class);
    }

    #[Test]
    public function person_is_notified_when_binding_assignment_is_removed(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $assignment = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        )->first();

        $this->service()->deleteAssignment($assignment->fresh(), true);
        $this->flushDeferred();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            static fn (ShiftNotification $notification): bool => str_starts_with(
                $notification->toArray()->notificationKey,
                'project-assignment-removed-'
            )
        );
    }

    #[Test]
    public function person_is_notified_when_wish_is_accepted(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $this->actingAs($worker);

        $wish = $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH,
            ['2026-08-02', '2026-08-03'],
            false
        )->first();

        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->service()->acceptWishGroup($wish->fresh());
        $this->flushDeferred();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            static fn (ShiftNotification $notification): bool => str_starts_with(
                $notification->toArray()->notificationKey,
                'project-assignment-wish_accepted-'
            )
        );
    }

    #[Test]
    public function person_is_notified_when_reschedule_dissolves_binding_single_days(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-05'],
            false
        );

        // Termin schrumpft auf 01.-03.08. — die Zuordnung am 05.08. wird aufgelöst
        $project->events()->firstOrFail()->update([
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-03 18:00:00',
        ]);
        $this->flushDeferred();

        Notification::assertSentTo(
            $worker,
            ShiftNotification::class,
            static fn (ShiftNotification $notification): bool => str_starts_with(
                $notification->toArray()->notificationKey,
                'project-assignment-removed-'
            )
        );
    }

    // ---------- Re-Materialisierung respektiert Frei-/Abwesenheits-Einträge ----------

    #[Test]
    public function rematerialization_keeps_days_dissolved_by_existing_free_entry(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );

        // Person trägt am 02.08. "Frei" ein — Zuordnung an dem Tag wird aufgelöst
        $worker->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => true,
            'type' => 'FREE_WORK',
            'comment' => 'FREE_WORK',
            'is_series' => false,
            'created_by' => $worker->id,
        ]);
        $this->service()->handleVacationEntry($worker, ['2026-08-02'], 'FREE_WORK');

        // Projektzeitraum verlängert sich bis 05.08. — der Frei-Tag darf dabei
        // NICHT wieder materialisiert werden, solange der Frei-Eintrag existiert
        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $project->events()->first()->event_type_id,
            'start_time' => '2026-08-04 10:00:00',
            'end_time' => '2026-08-05 18:00:00',
        ]);

        $activeDates = static fn () => ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->orderBy('date')
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->all();

        $this->assertSame(['2026-08-01', '2026-08-03', '2026-08-04', '2026-08-05'], $activeDates());

        // Wird der Frei-Eintrag gelöscht, kommt der Tag beim nächsten Zeitraum-Sync zurück
        $worker->vacations()->where('date', '2026-08-02')->delete();
        $this->service()->rematerializeForProjectPeriodChange($project->fresh());

        $this->assertSame(
            ['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05'],
            $activeDates()
        );
    }

    #[Test]
    public function rematerialization_respects_absence_for_wishes_but_not_bindings(): void
    {
        $this->actingAsAdmin();
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        // Verbindliche Zuordnung zuerst (danach angelegte Wünsche bleiben parallel bestehen)
        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING
        );
        $this->service()->createFullPeriodAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::WISH
        );

        // Abwesenheit am 02.08. löst nur den Wunsch auf, nicht die verbindliche Zuordnung
        $worker->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => true,
            'type' => 'OFF_WORK',
            'comment' => 'OFF_WORK',
            'is_series' => false,
            'created_by' => $worker->id,
        ]);
        $this->service()->handleVacationEntry($worker, ['2026-08-02'], 'OFF_WORK');

        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $project->events()->first()->event_type_id,
            'start_time' => '2026-08-04 10:00:00',
            'end_time' => '2026-08-05 18:00:00',
        ]);

        $datesOfType = static fn (string $type) => ProjectDayAssignment::query()
            ->where('project_id', $project->id)
            ->forEmployable(User::class, $worker->id)
            ->where('type', $type)
            ->orderBy('date')
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->all();

        // Abwesenheit blockiert nur die Wunsch-Re-Materialisierung
        $this->assertSame(
            ['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05'],
            $datesOfType('binding')
        );
        $this->assertSame(
            ['2026-08-01', '2026-08-03', '2026-08-04', '2026-08-05'],
            $datesOfType('wish')
        );
    }

    #[Test]
    public function project_assignment_response_uses_public_avatar_urls_for_external_workers(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');
        $freelancer = Freelancer::factory()->create(['profile_image' => 'avatars/freelancer.jpg']);
        $this->service()->createAssignments(
            $project,
            Freelancer::class,
            $freelancer->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02'],
            false
        );

        $response = $this->getJson(route('projects.day-assignments', $project))->assertOk();

        $this->assertStringContainsString(
            '/storage/avatars/freelancer.jpg',
            $response->json('assignments.0.worker.profile_photo_url')
        );
    }

    // ---------- Personen-Vorschläge (workerOptions, Schichten-Tab) ----------

    #[Test]
    public function worker_options_require_shift_planner_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');

        $this->getJson(route('projects.day-assignments.worker-options', $project))
            ->assertForbidden();
    }

    #[Test]
    public function worker_options_prioritize_project_team_and_annotate_existing_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-05');

        $teamMember = User::factory()->create([
            'first_name' => 'Zoe',
            'last_name' => 'Team',
            'can_work_shifts' => true,
        ]);
        $project->users()->attach($teamMember->id);

        $assignedWorker = User::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Assigned',
            'can_work_shifts' => true,
        ]);
        // Direkt anlegen — der Service würde die Person automatisch ins
        // Projektteam aufnehmen und die Sortier-Assertion verwässern
        $groupId = Str::uuid()->toString();
        foreach (['2026-08-02', '2026-08-03'] as $date) {
            ProjectDayAssignment::query()->create([
                'project_id' => $project->id,
                'employable_type' => User::class,
                'employable_id' => $assignedWorker->id,
                'type' => ProjectDayAssignmentType::BINDING->value,
                'date' => $date,
                'group_id' => $groupId,
                'is_full_period' => false,
            ]);
        }

        $notShiftCapable = User::factory()->create([
            'first_name' => 'Nora',
            'last_name' => 'NoShifts',
            'can_work_shifts' => false,
        ]);

        $response = $this->getJson(route('projects.day-assignments.worker-options', $project))
            ->assertOk();

        $workers = collect($response->json('workers'));

        // Nicht schichtfähige Personen tauchen nicht auf
        $this->assertFalse($workers->contains(fn (array $row) => $row['id'] === $notShiftCapable->id && $row['type'] === 0));

        // Projektteam-Mitglied steht vor Nicht-Team-Personen (trotz Name "Zoe" > "Anna")
        $teamRow = $workers->first(fn (array $row) => $row['id'] === $teamMember->id && $row['type'] === 0);
        $assignedRow = $workers->first(fn (array $row) => $row['id'] === $assignedWorker->id && $row['type'] === 0);
        $this->assertNotNull($teamRow);
        $this->assertNotNull($assignedRow);
        $this->assertTrue($teamRow['in_project_team']);
        $this->assertFalse($assignedRow['in_project_team']);
        $this->assertLessThan(
            $workers->search(fn (array $row) => $row['id'] === $assignedWorker->id && $row['type'] === 0),
            $workers->search(fn (array $row) => $row['id'] === $teamMember->id && $row['type'] === 0)
        );

        // Bestehende Zuordnung wird zusammengefasst
        $this->assertSame(2, $assignedRow['binding_days']);
        $this->assertFalse($assignedRow['has_full_period']);
        $this->assertSame(0, $assignedRow['wish_days']);
    }

    #[Test]
    public function worker_options_search_filters_by_name_across_worker_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-03');

        $match = User::factory()->create([
            'first_name' => 'Frieda',
            'last_name' => 'Findbar',
            'can_work_shifts' => true,
        ]);
        User::factory()->create([
            'first_name' => 'Otto',
            'last_name' => 'Anders',
            'can_work_shifts' => true,
        ]);
        $freelancerMatch = Freelancer::factory()->create([
            'first_name' => 'Frieda',
            'last_name' => 'Frei',
            'can_work_shifts' => true,
        ]);

        $response = $this->getJson(
            route('projects.day-assignments.worker-options', ['project' => $project, 'search' => 'Frieda'])
        )->assertOk();

        $workers = collect($response->json('workers'));

        $this->assertTrue($workers->contains(fn (array $row) => $row['id'] === $match->id && $row['type'] === 0));
        $this->assertTrue($workers->contains(fn (array $row) => $row['id'] === $freelancerMatch->id && $row['type'] === 1));
        $this->assertFalse($workers->contains(fn (array $row) => $row['name'] === 'Otto Anders'));
    }

    // ---------- Guards im Store-Endpoint ----------

    #[Test]
    public function store_rejects_workers_who_cannot_work_shifts(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => false]);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'binding',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['worker_id']);
    }

    #[Test]
    public function full_period_store_rejects_overlong_project_periods_before_materializing(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        // Tippfehler-Jahr: Zeitraum > MAX_FULL_PERIOD_DAYS
        $project = $this->createProjectWithPeriod('2026-08-01', '2029-08-01');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'binding',
            'full_period' => true,
            'days' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['project_id']);

        $this->assertSame(0, ProjectDayAssignment::query()->where('project_id', $project->id)->count());
    }

    #[Test]
    public function binding_on_absence_day_warns_first_and_saves_with_force(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $worker->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => true,
            'is_series' => false,
            'comment' => 'FREE_WORK',
            'type' => 'FREE_WORK',
            'created_by' => $worker->id,
        ]);

        $payload = [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $worker->id,
            'type' => 'binding',
            'full_period' => false,
            'days' => ['2026-08-02', '2026-08-03'],
        ];

        // Ohne force: 409-Warnung, nichts gespeichert
        $this->postJson(route('project-day-assignments.store'), $payload)
            ->assertStatus(409)
            ->assertJson(['warning' => 'absences', 'dates' => ['02.08.2026']]);
        $this->assertSame(0, ProjectDayAssignment::query()->where('project_id', $project->id)->count());

        // Mit force: bewusst trotzdem zuordnen
        $this->postJson(route('project-day-assignments.store'), $payload + ['force' => true])
            ->assertOk()
            ->assertJson(['created' => 2]);
    }

    #[Test]
    public function vacation_impact_endpoint_lists_assignments_dissolved_by_the_new_status(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $worker = User::factory()->create(['can_work_shifts' => true]);
        $other = User::factory()->create(['can_work_shifts' => true]);

        $this->service()->createAssignments(
            $project,
            User::class,
            $worker->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-08-02', '2026-08-03'],
            false
        );

        $payload = [
            'workers' => [
                ['type' => 0, 'id' => $worker->id, 'dates' => ['2026-08-02']],
                ['type' => 0, 'id' => $other->id, 'dates' => ['2026-08-02']],
            ],
            'vacation_type' => 'FREE_WORK',
        ];

        $response = $this->postJson(route('project-day-assignments.vacation-impact'), $payload)->assertOk();

        $affected = collect($response->json('affected'));
        $this->assertCount(1, $affected);
        $this->assertSame($project->id, $affected->first()['project_id']);
        $this->assertSame('binding', $affected->first()['type']);
        $this->assertSame(['02.08.2026'], $affected->first()['dates']);

        // Abwesenheit (kein "Frei") löst nur Wünsche auf — verbindliche Zuordnung bleibt unerwähnt
        $this->postJson(route('project-day-assignments.vacation-impact'), [
            'workers' => [['type' => 0, 'id' => $worker->id, 'dates' => ['2026-08-02']]],
            'vacation_type' => 'OFF_WORK',
        ])->assertOk()->assertJson(['affected' => []]);
    }

    #[Test]
    public function available_entries_do_not_block_wishes(): void
    {
        $project = $this->createProjectWithPeriod('2026-08-01', '2026-08-10');
        $user = User::factory()->create(['can_work_shifts' => true]);
        $this->actingAs($user);

        // Konkreter Verfügbarkeits-Eintrag (AVAILABLE) ist keine Abwesenheit
        $user->vacations()->create([
            'date' => '2026-08-02',
            'full_day' => false,
            'is_series' => false,
            'comment' => 'AVAILABLE',
            'type' => 'AVAILABLE',
            'created_by' => $user->id,
        ]);

        $this->postJson(route('project-day-assignments.store'), [
            'project_id' => $project->id,
            'worker_type' => 0,
            'worker_id' => $user->id,
            'type' => 'wish',
            'full_period' => false,
            'days' => ['2026-08-02'],
        ])->assertOk()->assertJson(['created' => 1]);
    }
}
