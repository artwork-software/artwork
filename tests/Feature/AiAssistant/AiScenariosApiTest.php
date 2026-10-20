<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Http\Requests\StoreAiConversationTurnRequest;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationEnum;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * ai-scenarios-3 S1–S8: Tagesuebersicht, Budget, Gewerk besetzen + Sammelzuweisung, Abwesenheiten,
 * Aufgaben, Doppelbelegungen, Arbeitszeitkonto, Projektdetails.
 */
final class AiScenariosApiTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private const DAY = '2026-11-03';

    private Craft $craft;

    private ShiftQualification $qualification;

    private Room $room;

    private Project $project;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $aiClient = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);

        config(['ai.enabled' => true]);
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $aiClient->getKey(),
        ]);

        $this->craft = Craft::factory()->create(['name' => 'Ton', 'abbreviation' => 'TON', 'assignable_by_all' => true]);
        $this->qualification = ShiftQualification::factory()->create(['name' => 'Meister']);
        $this->room = Room::factory()->create(['name' => 'Studio 1']);
        $this->project = Project::factory()->create(['name' => 'Tosca']);
        $this->event = $this->makeEvent('Vorstellung', self::DAY . ' 19:00:00', self::DAY . ' 22:00:00');
    }

    // --- Helfer ---------------------------------------------------------------------------------

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();
        $token = $user->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function grant(User $user, string ...$permissions): User
    {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->fresh();
    }

    private function planner(): User
    {
        return $this->grant(
            User::factory()->create(),
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::VIEW_SHIFT_PLAN->value
        );
    }

    private function makeEvent(string $name, string $start, string $end, ?Project $project = null, ?Room $room = null): Event
    {
        return Event::factory()->create([
            'room_id' => ($room ?? $this->room)->id,
            'project_id' => ($project ?? $this->project)->id,
            'eventName' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'is_planning' => false,
        ]);
    }

    /** @param array<string, float> $rules */
    private function worker(string $lastName, array $rules = []): User
    {
        [$user, $contract] = $this->userWithContract();
        foreach ($rules as $trigger => $value) {
            $this->ruleForContract($contract, $trigger, $value, ['name' => $trigger]);
        }
        $user->forceFill(['first_name' => 'Ada', 'last_name' => $lastName, 'can_work_shifts' => true])->save();
        $user->assignedCrafts()->attach($this->craft->id);
        $user->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);

        return $user->fresh();
    }

    private function shift(string $date, string $start, string $end, int $demand = 1): Shift
    {
        $shift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'event_id' => $this->event->id,
            'craft_id' => $this->craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => $start,
            'end' => $end,
            'break_minutes' => 0,
            'is_committed' => false,
            'shift_group_id' => null,
        ]));
        ShiftsQualifications::query()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $this->qualification->id,
            'value' => $demand,
        ]);

        return $shift;
    }

    private function assign(Shift $shift, User $user): void
    {
        $shift->users()->attach($user->id, ['shift_qualification_id' => $this->qualification->id, 'shift_count' => 1]);
    }

    /** @param array<string, mixed> $args */
    private function confirmedAction(User $user, string $tool, array $args): AiConversationTurn
    {
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'KI',
            'status' => AiConversation::STATUS_AWAITING_CONFIRMATION,
        ]);

        return $conversation->turns()->create([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Bitte ausfuehren',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'tool_calls' => [],
            'context' => [],
            'pending_action' => [
                'tool' => $tool,
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => 'Ausfuehren', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    private function writeToken(User $user, AiConversationTurn $turn): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->actingAs($user)->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk()->json('access_token');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    private function revert(User $user, string $batchId): void
    {
        $this->app['auth']->forgetGuards();
        $this->withoutHeader('Authorization')
            ->actingAs($user)
            ->postJson('/ai/actions/batches/' . $batchId . '/revert')
            ->assertOk();
    }

    /**
     * Budgettabelle: drei Strukturspalten + eine Wertspalte; Kosten 1000 (+ 50 auskommentiert),
     * Erloese 1500.
     */
    private function budget(Project $project): Column
    {
        $table = Table::query()->create(['project_id' => $project->id, 'name' => 'Budget', 'is_template' => false]);
        $columns = [];
        foreach (['KTO', 'KST', 'Position', 'Plan'] as $position => $name) {
            $columns[] = Column::query()->create([
                'table_id' => $table->id,
                'name' => $name,
                'subName' => $position === 3 ? 'Soll' : '',
                'type' => 'empty',
                'position' => $position,
                'relevant_for_project_groups' => $position === 3,
            ]);
        }

        $rows = [
            ['BUDGET_TYPE_COST', 'Personal', [['1000', false], ['50', true]]],
            ['BUDGET_TYPE_EARNING', 'Karten', [['1500', false]]],
        ];
        foreach ($rows as $index => [$type, $name, $values]) {
            $main = MainPosition::query()->create([
                'table_id' => $table->id, 'type' => $type, 'name' => $name, 'position' => $index,
            ]);
            $sub = SubPosition::query()->create(['main_position_id' => $main->id, 'name' => $name, 'position' => 0]);
            foreach ($values as $order => [$value, $commented]) {
                $row = SubPositionRow::query()->create([
                    'sub_position_id' => $sub->id, 'commented' => $commented, 'position' => $order, 'order' => $order,
                ]);
                foreach ($columns as $column) {
                    ColumnCell::query()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $row->id,
                        'value' => $column->position === 3 ? $value : '',
                    ]);
                }
            }
        }

        return $columns[3];
    }

    // --- S1 Tagesuebersicht --------------------------------------------------------------------

    #[Test]
    public function the_day_overview_lists_events_shifts_and_due_tasks_and_hides_the_planner_block(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user);
        $foreignProject = Project::factory()->create();
        $shiftEvent = $this->makeEvent('Gastspiel', self::DAY . ' 10:00:00', self::DAY . ' 12:00:00', $foreignProject);
        $shift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'event_id' => $shiftEvent->id, 'craft_id' => $this->craft->id, 'start_date' => self::DAY,
            'end_date' => self::DAY, 'start' => '09:00', 'end' => '13:00', 'is_committed' => true,
        ]));
        $this->assign($shift, $user);
        $this->makeEvent('Anderswo', self::DAY . ' 10:00:00', self::DAY . ' 11:00:00', Project::factory()->create());
        $checklist = Checklist::factory()->create(['user_id' => $user->id, 'project_id' => null]);
        Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Faellig', 'done' => false, 'deadline' => self::DAY . ' 12:00:00']);
        Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Spaeter', 'done' => false, 'deadline' => '2026-12-01 12:00:00']);
        $this->actingAsToken($user);

        $response = $this->getJson('/api/v1/ai/day-overview?date=' . self::DAY)->assertOk();

        $this->assertSame(self::DAY, $response->json('date'));
        $this->assertEqualsCanonicalizing(
            ['Vorstellung', 'Gastspiel'],
            array_column($response->json('events.data'), 'name')
        );
        $this->assertSame(2, $response->json('events.total'));
        $this->assertSame([$shift->id], array_column($response->json('my_shifts.shifts.data'), 'id'));
        $this->assertSame(['Faellig'], array_column($response->json('my_tasks_due'), 'name'));
        $this->assertNull($response->json('planner'));
    }

    #[Test]
    public function planners_see_open_shifts_absences_and_room_conflicts_of_the_day(): void
    {
        $this->shift(self::DAY, '08:00', '16:00', 2);
        $absent = $this->worker('Weg');
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $absent->id,
            'date' => self::DAY, 'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
        ]);
        $this->makeEvent('Probe', self::DAY . ' 20:00:00', self::DAY . ' 23:00:00');
        $this->actingAsToken($this->planner());

        $response = $this->getJson('/api/v1/ai/day-overview?date=' . self::DAY)->assertOk();

        $this->assertSame(1, $response->json('planner.open_shifts.shifts'));
        $this->assertSame(2, $response->json('planner.open_shifts.missing'));
        $this->assertSame([['craft' => ['id' => $this->craft->id, 'name' => 'Ton'], 'missing' => 2]], $response->json('planner.open_shifts.by_craft'));
        $this->assertSame(1, $response->json('planner.absences.count'));
        $this->assertSame('vacation', $response->json('planner.absences.data.0.type'));
        $this->assertSame(1, $response->json('planner.room_conflicts.count'));
    }

    // --- S2 Budget ------------------------------------------------------------------------------

    #[Test]
    public function the_budget_summary_reports_plan_values_for_people_with_budget_access(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user, ['access_budget' => true]);
        $column = $this->budget($this->project);
        $this->actingAsToken($user);

        $response = $this->getJson("/api/v1/ai/projects/{$this->project->id}/budget-summary")->assertOk();

        $this->assertTrue($response->json('has_budget'));
        $this->assertSame(['id' => $column->id, 'name' => 'Plan', 'sub_name' => 'Soll'], $response->json('column'));
        $this->assertSame('1000.00', $response->json('costs'));
        $this->assertSame('1500.00', $response->json('earnings'));
        $this->assertSame('500.00', $response->json('result'));
        $this->assertTrue($response->json('profitable'));
        $this->assertSame(['costs' => '50.00', 'earnings' => '0.00'], $response->json('commented'));
        $this->assertSame(
            [['name' => 'Personal', 'type' => 'cost', 'sum' => '1000.00'], ['name' => 'Karten', 'type' => 'earning', 'sum' => '1500.00']],
            $response->json('main_positions')
        );
        $this->assertNull($response->json('actual'));
    }

    #[Test]
    public function the_budget_summary_needs_budget_access_and_a_visible_project(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user);
        $this->budget($this->project);
        $this->actingAsToken($user);

        $this->getJson("/api/v1/ai/projects/{$this->project->id}/budget-summary")
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_budget_access']);

        $hidden = Project::factory()->create();
        $this->getJson("/api/v1/ai/projects/{$hidden->id}/budget-summary")->assertNotFound();
    }

    #[Test]
    public function a_project_without_budget_table_has_no_budget(): void
    {
        $this->actingAsToken($this->adminUser());

        $this->getJson("/api/v1/ai/projects/{$this->project->id}/budget-summary")
            ->assertOk()
            ->assertJsonPath('has_budget', false)
            ->assertJsonPath('costs', null)
            ->assertJsonPath('project.name', 'Tosca');
    }

    // --- S3 Gewerk besetzen ---------------------------------------------------------------------

    #[Test]
    public function staffing_an_event_fills_open_slots_without_violations_and_writes_nothing(): void
    {
        $tired = $this->worker('Muede', ['maxWorkingHoursOnDay' => 10.0]);
        $otherShift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'craft_id' => $this->craft->id, 'start_date' => self::DAY, 'end_date' => self::DAY,
            'start' => '06:00', 'end' => '14:00', 'break_minutes' => 0, 'is_committed' => false,
        ]));
        $this->assign($otherShift, $tired);
        $fresh = $this->worker('Frisch');
        $morning = $this->shift(self::DAY, '15:00', '19:00', 1);
        $evening = $this->shift(self::DAY, '18:00', '23:00', 1);
        $this->actingAsToken($this->planner());
        $before = [ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()];

        $response = $this->postJson('/api/v1/ai/shift-plan/staff-event', [
            'event_id' => $this->event->id,
            'craft_id' => $this->craft->id,
        ])->assertOk();

        // Frisch bekommt genau eine der beiden ueberlappenden Schichten, Muede keine (Tagesmaximum).
        $this->assertCount(1, $response->json('picks'));
        $this->assertSame($fresh->id, $response->json('picks.0.person.id'));
        $this->assertSame($morning->id, $response->json('picks.0.shift_id'));
        $this->assertSame($this->qualification->id, $response->json('picks.0.qualification.id'));
        $this->assertSame($evening->id, $response->json('unfilled.0.shift_id'));
        $this->assertSame('all_candidates_violate_rules', $response->json('unfilled.0.reason_code'));
        $this->assertSame(['slots' => 2, 'filled' => 1, 'unfilled' => 1], $response->json('totals'));
        $this->assertSame($before, [ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()]);
    }

    #[Test]
    public function bulk_assignments_preview_execute_and_undo_as_one_batch(): void
    {
        $planner = $this->planner();
        $ada = $this->worker('Eins');
        $bob = $this->worker('Zwei');
        $shift = $this->shift(self::DAY, '10:00', '14:00', 2);
        $args = ['assignments' => [
            ['person' => ['type' => 'user', 'id' => $ada->id], 'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id],
            ['person' => ['type' => 'user', 'id' => $bob->id], 'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id],
        ]];
        $this->actingAsToken($planner);

        $preview = $this->postJson('/api/v1/ai/shift-assignments/bulk', $args)->assertOk();
        $this->assertSame('assign_shifts_bulk', $preview->json('tool'));
        $this->assertSame(['type' => 'user', 'id' => $ada->id, 'name' => 'Ada Eins'], $preview->json('items.0.person'));
        $this->assertSame(0, $preview->json('summary.with_conflicts'));
        $this->assertSame(0, $shift->users()->count());

        $turn = $this->confirmedAction($planner, 'assign_shifts_bulk', $args);
        $this->writeToken($planner, $turn);
        $result = $this->postJson('/api/v1/ai/shift-assignments/bulk', array_merge($args, [
            'dry_run' => false, 'action_id' => $turn->id,
        ]))->assertOk()->json();

        $this->assertSame(2, $shift->users()->count());
        $this->revert($planner, $result['batch_id']);
        $this->assertSame(0, $shift->users()->count());
    }

    #[Test]
    public function bulk_assignments_beyond_the_open_demand_are_conflicts(): void
    {
        $ada = $this->worker('Eins');
        $bob = $this->worker('Zwei');
        $shift = $this->shift(self::DAY, '10:00', '14:00', 1);
        $this->actingAsToken($this->planner());

        $response = $this->postJson('/api/v1/ai/shift-assignments/bulk', ['assignments' => [
            ['person' => ['type' => 'user', 'id' => $ada->id], 'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id],
            ['person' => ['type' => 'user', 'id' => $bob->id], 'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id],
        ]])->assertOk();

        $this->assertSame([], $response->json('items.0.conflicts'));
        $this->assertSame('no_open_demand', $response->json('items.1.conflicts.0.type'));
        $this->assertContains('assign_shifts_bulk', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
        $this->assertContains('update_task', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    // --- S4 Abwesenheiten -----------------------------------------------------------------------

    #[Test]
    public function absences_are_merged_per_person_and_need_shift_plan_rights(): void
    {
        $person = $this->worker('Urlaub');
        foreach (['2026-11-02', '2026-11-03', '2026-11-04'] as $date) {
            Vacation::query()->create([
                'vacationer_type' => User::class, 'vacationer_id' => $person->id,
                'date' => $date, 'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
            ]);
        }
        $freelancer = Freelancer::factory()->create(['first_name' => 'Fritz', 'last_name' => 'Frei']);
        Vacation::query()->create([
            'vacationer_type' => Freelancer::class, 'vacationer_id' => $freelancer->id, 'date' => '2026-11-05',
            'full_day' => false, 'start_time' => '10:00', 'end_time' => '12:00', 'type' => VacationEnum::NOT_AVAILABLE->value,
        ]);

        $this->actingAsToken(User::factory()->create());
        $this->getJson('/api/v1/ai/absences?from=2026-11-02&to=2026-11-08')
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_shift_plan_access']);

        $this->actingAsToken($this->grant(User::factory()->create(), PermissionEnum::VIEW_SHIFT_PLAN->value));
        $response = $this->getJson('/api/v1/ai/absences?from=2026-11-02T00:00:00%2B01:00&to=2026-11-08T23:59:59%2B01:00')
            ->assertOk();

        $this->assertSame(2, $response->json('total'));
        $rows = collect($response->json('data'))->keyBy('person.name');
        $this->assertSame([[
            'from' => '2026-11-02', 'to' => '2026-11-04', 'full_day' => true,
            'start_time' => null, 'end_time' => null, 'type' => 'vacation',
        ]], $rows['Ada Urlaub']['absences']);
        $this->assertSame('not_available', $rows['Fritz Frei']['absences'][0]['type']);
        $this->assertSame('10:00', $rows['Fritz Frei']['absences'][0]['start_time']);
    }

    // --- S5 Aufgaben ----------------------------------------------------------------------------

    #[Test]
    public function my_tasks_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $checklist = Checklist::factory()->create(['user_id' => $user->id, 'project_id' => null, 'name' => 'Meine Liste']);
        Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Ueberfaellig', 'done' => false, 'deadline' => '2020-01-01 10:00:00']);
        Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Offen', 'done' => false, 'deadline' => null]);
        Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Fertig', 'done' => true]);
        Task::factory()->create(['name' => 'Fremd', 'done' => false]);
        $this->actingAsToken($user);

        $open = $this->getJson('/api/v1/ai/me/tasks?status=open')->assertOk();
        $this->assertSame(['Ueberfaellig', 'Offen'], array_column($open->json('data'), 'name'));
        $this->assertTrue($open->json('data.0.overdue'));
        $this->assertSame(['id' => $checklist->id, 'name' => 'Meine Liste'], $open->json('data.0.checklist'));
        $this->assertSame(['Ueberfaellig'], array_column($this->getJson('/api/v1/ai/me/tasks?status=overdue')->json('data'), 'name'));
        $this->assertSame(['Fertig'], array_column($this->getJson('/api/v1/ai/me/tasks?status=done')->json('data'), 'name'));
    }

    #[Test]
    public function a_task_can_be_ticked_off_and_the_undo_restores_it(): void
    {
        $user = User::factory()->create();
        $checklist = Checklist::factory()->create(['user_id' => $user->id, 'project_id' => null]);
        $task = Task::factory()->create(['checklist_id' => $checklist->id, 'done' => false]);
        $foreign = Task::factory()->create(['done' => false]);
        $this->actingAsToken($user);

        $this->patchJson("/api/v1/ai/tasks/{$foreign->id}", ['done' => true])->assertNotFound();
        $this->patchJson("/api/v1/ai/tasks/{$task->id}", ['task_id' => $task->id + 1, 'done' => true])->assertUnprocessable();
        $this->patchJson("/api/v1/ai/tasks/{$task->id}", ['task_id' => $task->id, 'done' => true])
            ->assertOk()
            ->assertJsonPath('tool', 'update_task')
            ->assertJsonPath('items.0.action', 'update');
        $this->assertFalse($task->fresh()->done);

        $args = ['task_id' => $task->id, 'done' => true];
        $turn = $this->confirmedAction($user, 'update_task', $args);
        $this->writeToken($user, $turn);
        $result = $this->patchJson("/api/v1/ai/tasks/{$task->id}", array_merge($args, [
            'dry_run' => false, 'action_id' => $turn->id,
        ]))->assertOk()->json();

        $this->assertTrue($task->fresh()->done);
        $this->assertSame($user->id, $task->fresh()->user_id);

        $this->revert($user, $result['batch_id']);
        $this->assertFalse($task->fresh()->done);
        $this->assertNull($task->fresh()->done_at);
    }

    // --- S6 Doppelbelegungen --------------------------------------------------------------------

    #[Test]
    public function room_conflicts_are_reported_and_foreign_projects_stay_anonymous(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user);
        $this->makeEvent('Fremdprobe', self::DAY . ' 21:00:00', self::DAY . ' 23:00:00', Project::factory()->create());
        $this->makeEvent('Anderer Raum', self::DAY . ' 19:00:00', self::DAY . ' 22:00:00', null, Room::factory()->create());
        $this->actingAsToken($user);

        $response = $this->getJson('/api/v1/ai/rooms/conflicts?from=' . self::DAY . '&to=' . self::DAY)->assertOk();

        $this->assertSame(1, $response->json('total'));
        $this->assertSame(['id' => $this->room->id, 'name' => 'Studio 1'], $response->json('data.0.room'));
        $this->assertStringStartsWith(self::DAY . 'T21:00', $response->json('data.0.start'));
        $this->assertStringStartsWith(self::DAY . 'T22:00', $response->json('data.0.end'));
        $this->assertSame('Vorstellung', $response->json('data.0.events.0.name'));
        $this->assertSame(['id' => null, 'name' => 'belegt', 'project' => null], [
            'id' => $response->json('data.0.events.1.id'),
            'name' => $response->json('data.0.events.1.name'),
            'project' => $response->json('data.0.events.1.project'),
        ]);
    }

    // --- S7 Arbeitszeitkonto --------------------------------------------------------------------

    #[Test]
    public function the_work_time_account_is_visible_to_oneself_and_hour_viewers_only(): void
    {
        $person = $this->worker('Konto');
        $this->assign($this->shift(self::DAY, '08:00', '16:00'), $person);
        $this->actingAsToken($person);

        $response = $this->getJson("/api/v1/ai/people/user/{$person->id}/work-time?month=2026-11")->assertOk();
        $this->assertSame('2026-11', $response->json('month'));
        $this->assertSame($person->id, $response->json('person.id'));
        $this->assertIsInt($response->json('actual_minutes'));
        $this->assertIsInt($response->json('balance_minutes'));

        $this->actingAsToken(User::factory()->create());
        $this->getJson("/api/v1/ai/people/user/{$person->id}/work-time?month=2026-11")
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_work_time_access']);

        $this->actingAsToken($this->grant(User::factory()->create(), PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value));
        $this->getJson("/api/v1/ai/people/user/{$person->id}/work-time?month=2026-11")->assertOk();

        $freelancer = Freelancer::factory()->create();
        $this->getJson("/api/v1/ai/people/freelancer/{$freelancer->id}/work-time?month=2026-11")
            ->assertUnprocessable()
            ->assertExactJson(['error' => 'no_work_time_account']);
    }

    // --- S8 Projektdetails ----------------------------------------------------------------------

    #[Test]
    public function the_project_detail_carries_managers_deadline_and_open_tasks(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create(['first_name' => 'Mia', 'last_name' => 'Leitung']);
        $this->project->users()->attach($user);
        $this->project->users()->attach($manager, ['is_manager' => true]);
        $this->project->update(['budget_deadline' => '2026-12-31', 'marked_as_done' => false]);
        $checklist = Checklist::factory()->create(['project_id' => $this->project->id, 'user_id' => $user->id]);
        Task::factory()->count(2)->create(['checklist_id' => $checklist->id, 'done' => false]);
        Task::factory()->create(['checklist_id' => $checklist->id, 'done' => true]);
        $this->actingAsToken($user);

        $response = $this->getJson("/api/v1/ai/projects/{$this->project->id}")->assertOk();

        $this->assertSame([['id' => $manager->id, 'name' => 'Mia Leitung']], $response->json('managers'));
        $this->assertSame('2026-12-31', $response->json('budget_deadline'));
        $this->assertFalse($response->json('marked_as_done'));
        $this->assertFalse($response->json('is_group'));
        $this->assertSame(2, $response->json('open_tasks'));
    }
}
