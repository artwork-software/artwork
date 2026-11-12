<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Http\Requests\StoreAiConversationTurnRequest;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
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
use Tests\Concerns\GrantsAiAccess;

/**
 * ai-scenarios-4 S9–S13 (Core): Ausfall ersetzen, Aufgabe anlegen, Abwesenheit eintragen,
 * Termine gesammelt verschieben, Schichten anlegen und kopieren.
 */
final class AiScenariosFourApiTest extends TestCase
{
    use GrantsAiAccess;

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
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
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
    private function worker(string $lastName, array $rules = [], ?Craft $craft = null): User
    {
        $craft ??= $this->craft;
        [$user, $contract] = $this->userWithContract();
        foreach ($rules as $trigger => $value) {
            $this->ruleForContract($contract, $trigger, $value, ['name' => $trigger]);
        }
        $user->forceFill(['first_name' => 'Ada', 'last_name' => $lastName, 'can_work_shifts' => true])->save();
        $user->assignedCrafts()->attach($craft->id);
        $user->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $craft->id]);

        return $user->fresh();
    }

    private function shift(
        string $date,
        string $start,
        string $end,
        int $demand = 1,
        ?Craft $craft = null,
        ?Event $event = null
    ): Shift {
        $shift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'event_id' => ($event ?? $this->event)->id,
            'craft_id' => ($craft ?? $this->craft)->id,
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

    /**
     * Bestaetigte Aktion + Write-Token, dann ausfuehren.
     *
     * @param array<string, mixed> $args
     * @return \Illuminate\Testing\TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function execute(User $user, string $method, string $uri, string $tool, array $args): \Illuminate\Testing\TestResponse
    {
        $turn = $this->confirmedAction($user, $tool, $args);
        $this->app['auth']->forgetGuards();
        $token = $this->actingAs($user)->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk()->json('access_token');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $token);

        return $this->json($method, $uri, array_merge($args, ['dry_run' => false, 'action_id' => $turn->id]));
    }

    private function revert(User $user, string $batchId, int $status = 200): void
    {
        $this->app['auth']->forgetGuards();
        $this->withoutHeader('Authorization')
            ->actingAs($user)
            ->postJson('/ai/actions/batches/' . $batchId . '/revert')
            ->assertStatus($status);
    }

    /** @return list<int> */
    private function workerIds(Shift $shift): array
    {
        return $shift->users()->pluck('users.id')->map(static fn ($id): int => (int) $id)->sort()->values()->all();
    }

    private function vacationType(Vacation $vacation): string
    {
        return $vacation->type instanceof VacationEnum ? $vacation->type->value : (string) $vacation->type;
    }

    // --- S9 Ausfall ersetzen --------------------------------------------------------------------

    #[Test]
    public function replacements_are_rule_conform_without_double_booking_and_write_nothing(): void
    {
        $sick = $this->worker('Krank');
        $berger = $this->worker('Berger');
        $meier = $this->worker('Meier');
        $zander = $this->worker('Zander');
        $light = Craft::factory()->create(['name' => 'Licht', 'abbreviation' => 'LI', 'assignable_by_all' => true]);
        $sick->assignedCrafts()->attach($light->id);
        $first = $this->shift(self::DAY, '10:00', '14:00');
        $second = $this->shift(self::DAY, '13:00', '17:00');
        $lonely = $this->shift(self::DAY, '18:00', '20:00', 1, $light);
        foreach ([$first, $second, $lonely] as $shift) {
            $this->assign($shift, $sick);
        }
        $this->actingAsToken($this->planner());
        $before = [ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()];

        $response = $this->postJson('/api/v1/ai/shift-plan/replacements', [
            'person' => ['type' => 'user', 'id' => $sick->id],
            'from' => self::DAY,
            'to' => '2026-11-03T23:59:00+01:00',
        ])->assertOk();

        $rows = collect($response->json('shifts'))->keyBy('shift_id');
        $this->assertSame($sick->id, $response->json('person.id'));
        $this->assertSame($berger->id, $rows[$first->id]['replacement']['id']);
        $this->assertSame([$meier->id, $zander->id], array_column($rows[$first->id]['alternatives'], 'id'));
        // Berger hat schon die ueberlappende erste Schicht → zweite geht an Meier.
        $this->assertSame($meier->id, $rows[$second->id]['replacement']['id']);
        $this->assertNotContains($berger->id, array_column($rows[$second->id]['alternatives'], 'id'));
        $this->assertNull($rows[$lonely->id]['replacement']);
        $this->assertSame('no_eligible_people', $rows[$lonely->id]['reason_code']);
        $this->assertSame(['shifts' => 3, 'covered' => 2, 'uncovered' => 1], $response->json('totals'));
        foreach ($rows as $row) {
            $this->assertNotSame($sick->id, $row['replacement']['id'] ?? null);
        }
        $this->assertSame($before, [ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()]);
    }

    #[Test]
    public function replacements_need_the_planner_right_and_a_known_person(): void
    {
        $sick = $this->worker('Krank');
        $this->actingAsToken(User::factory()->create());
        $this->postJson('/api/v1/ai/shift-plan/replacements', [
            'person' => ['type' => 'user', 'id' => $sick->id], 'from' => self::DAY, 'to' => self::DAY,
        ])->assertForbidden();

        $this->actingAsToken($this->planner());
        $this->postJson('/api/v1/ai/shift-plan/replacements', [
            'person' => ['type' => 'user', 'id' => 999999], 'from' => self::DAY, 'to' => self::DAY,
        ])->assertNotFound();
        $this->postJson('/api/v1/ai/shift-plan/replacements', [
            'person' => ['type' => 'user', 'id' => $sick->id], 'from' => self::DAY, 'to' => '2027-03-01',
        ])->assertUnprocessable();
    }

    #[Test]
    public function replacing_with_mark_absent_executes_in_one_batch_and_the_undo_restores_everything(): void
    {
        $planner = $this->planner();
        $sick = $this->worker('Krank');
        $berger = $this->worker('Berger');
        $shift = $this->shift(self::DAY, '10:00', '14:00');
        $this->assign($shift, $sick);
        $args = [
            'replacements' => [[
                'shift_id' => $shift->id,
                'shift_qualification_id' => $this->qualification->id,
                'from' => ['type' => 'user', 'id' => $sick->id],
                'to' => ['type' => 'user', 'id' => $berger->id],
            ]],
            'mark_absent' => ['type' => 'not_available'],
        ];
        $this->actingAsToken($planner);

        $preview = $this->postJson('/api/v1/ai/shift-assignments/replace', $args)->assertOk();
        $this->assertSame('replace_in_shifts', $preview->json('tool'));
        $this->assertSame(['type' => 'user', 'id' => $berger->id, 'name' => 'Ada Berger'], $preview->json('items.0.person'));
        $this->assertSame([], $preview->json('items.0.conflicts'));
        $this->assertSame(['type' => 'user', 'id' => $sick->id, 'name' => 'Ada Krank'], $preview->json('items.1.person'));
        $this->assertSame(0, $preview->json('summary.blocked'));
        $this->assertSame([$sick->id], $this->workerIds($shift));
        $this->assertSame(0, Vacation::query()->where('vacationer_id', $sick->id)->count());

        $result = $this->execute($planner, 'POST', '/api/v1/ai/shift-assignments/replace', 'replace_in_shifts', $args)
            ->assertOk()->json();

        $this->assertSame([$berger->id], $this->workerIds($shift));
        $vacation = Vacation::query()->where('vacationer_type', User::class)->where('vacationer_id', $sick->id)->sole();
        $this->assertSame('NOT_AVAILABLE', $this->vacationType($vacation));
        $this->assertSame(self::DAY, CarbonImmutable::parse($vacation->date)->toDateString());

        $this->revert($planner, $result['batch_id']);
        $this->assertSame([$sick->id], $this->workerIds($shift));
        $this->assertSame(0, Vacation::query()->where('vacationer_id', $sick->id)->where('vacationer_type', User::class)->count());
    }

    #[Test]
    public function replacing_with_a_rule_violation_or_without_assignment_is_refused(): void
    {
        $planner = $this->planner();
        $sick = $this->worker('Krank');
        $tired = $this->worker('Muede', ['maxWorkingHoursOnDay' => 10.0]);
        $other = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'craft_id' => $this->craft->id, 'start_date' => self::DAY, 'end_date' => self::DAY,
            'start' => '06:00', 'end' => '14:00', 'break_minutes' => 0, 'is_committed' => false,
        ]));
        $this->assign($other, $tired);
        $shift = $this->shift(self::DAY, '15:00', '19:00');
        $this->assign($shift, $sick);
        $notTheirs = $this->shift(self::DAY, '08:00', '09:00');
        $args = ['replacements' => [
            [
                'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id,
                'from' => ['type' => 'user', 'id' => $sick->id], 'to' => ['type' => 'user', 'id' => $tired->id],
            ],
            [
                'shift_id' => $notTheirs->id, 'shift_qualification_id' => $this->qualification->id,
                'from' => ['type' => 'user', 'id' => $sick->id], 'to' => ['type' => 'user', 'id' => $tired->id],
            ],
        ]];
        $this->actingAsToken($planner);

        $preview = $this->postJson('/api/v1/ai/shift-assignments/replace', $args)->assertOk();
        $types = collect($preview->json('items'))->keyBy('id')->map(
            static fn (array $item): array => array_column($item['conflicts'], 'type')
        );
        $this->assertContains('rule:maxWorkingHoursOnDay', $types[$shift->id]);
        $this->assertContains('not_assigned', $types[$notTheirs->id]);

        $this->postJson('/api/v1/ai/shift-assignments/replace', ['replacements' => [[
            'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id,
            'from' => ['type' => 'user', 'id' => $sick->id], 'to' => ['type' => 'user', 'id' => $sick->id],
        ]]])->assertUnprocessable();

        $this->execute($planner, 'POST', '/api/v1/ai/shift-assignments/replace', 'replace_in_shifts', $args)
            ->assertUnprocessable();
        $this->assertSame([$sick->id], $this->workerIds($shift));
        $this->assertSame([], $this->workerIds($notTheirs));
        $this->assertContains('replace_in_shifts', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    // --- S11 Abwesenheit eintragen --------------------------------------------------------------

    #[Test]
    public function an_own_absence_warns_about_shifts_executes_and_is_undone(): void
    {
        $user = $this->worker('Selbst');
        $shift = $this->shift(self::DAY, '10:00', '14:00');
        $this->assign($shift, $user);
        $args = [
            'person' => ['type' => 'user', 'id' => $user->id],
            'days' => ['from_day' => self::DAY, 'to_day' => '2026-11-04'],
            'type' => 'vacation',
            'full_day' => true,
            'comment' => 'KI',
        ];
        $this->actingAsToken($user);

        $preview = $this->postJson('/api/v1/ai/absences', $args)->assertOk();
        $this->assertSame('create_absence', $preview->json('tool'));
        $this->assertCount(2, $preview->json('items'));
        $this->assertSame('has_shifts', $preview->json('items.0.conflicts.0.type'));
        $this->assertSame([], $preview->json('items.1.conflicts'));
        $this->assertSame(0, $preview->json('summary.blocked'));
        $this->assertSame(0, Vacation::query()->where('vacationer_id', $user->id)->count());

        $result = $this->execute($user, 'POST', '/api/v1/ai/absences', 'create_absence', $args)->assertOk()->json();
        $vacations = Vacation::query()->where('vacationer_type', User::class)->where('vacationer_id', $user->id)->get();
        $this->assertCount(2, $vacations);
        $this->assertSame(['OFF_WORK'], $vacations->map(fn (Vacation $v): string => $this->vacationType($v))->unique()->values()->all());
        $this->assertTrue((bool) $vacations->first()->full_day);
        // Die Schicht bleibt bestehen (Hinweis auf S9 im Prompt).
        $this->assertSame([$user->id], $this->workerIds($shift));

        $this->revert($user, $result['batch_id']);
        $this->assertSame(0, Vacation::query()->where('vacationer_type', User::class)->where('vacationer_id', $user->id)->count());
    }

    #[Test]
    public function absences_for_others_need_availability_rights_and_valid_times(): void
    {
        $person = $this->worker('Fremd');
        $args = [
            'person' => ['type' => 'user', 'id' => $person->id],
            'days' => [self::DAY],
            'type' => 'not_available',
            'full_day' => ['start_time' => '10:00', 'end_time' => '12:00'],
        ];

        $this->actingAsToken(User::factory()->create());
        $this->postJson('/api/v1/ai/absences', $args)->assertForbidden()->assertExactJson(['error' => 'no_availability_access']);

        $this->actingAsToken($this->grant(User::factory()->create(), PermissionEnum::AVAILABILITY_MANAGEMENT->value));
        $this->postJson('/api/v1/ai/absences', $args)
            ->assertOk()
            ->assertJsonPath('items.0.person.id', $person->id)
            ->assertJsonPath('items.0.start', '2026-11-03T10:00:00+01:00');
        $this->postJson('/api/v1/ai/absences', array_merge($args, ['full_day' => ['start_time' => '12:00', 'end_time' => '10:00']]))
            ->assertUnprocessable();
        $this->postJson('/api/v1/ai/absences', array_merge($args, ['days' => ['03.11.2026']]))->assertUnprocessable();
        $this->postJson('/api/v1/ai/absences', array_merge($args, ['person' => ['type' => 'user', 'id' => 999999]]))
            ->assertNotFound();
        $this->assertContains('create_absence', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    // --- S10 Aufgabe anlegen --------------------------------------------------------------------

    #[Test]
    public function a_private_task_lands_in_a_new_private_checklist_and_the_undo_removes_both(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create();
        $args = [
            'name' => 'Mikrofone bestellen',
            'description' => 'Vier Stueck',
            'deadline' => '2026-11-05T09:00:00+01:00',
            'assignee_ids' => [$colleague->id],
        ];
        $this->actingAsToken($user);

        $preview = $this->postJson('/api/v1/ai/tasks', $args)->assertOk();
        $this->assertSame('create_task', $preview->json('tool'));
        $this->assertSame('new_checklist', $preview->json('items.0.conflicts.0.type'));
        $this->assertSame(0, $preview->json('summary.blocked'));
        $this->assertSame(0, Task::query()->where('name', 'Mikrofone bestellen')->count());

        $result = $this->execute($user, 'POST', '/api/v1/ai/tasks', 'create_task', $args)->assertOk()->json();
        $task = Task::query()->where('name', 'Mikrofone bestellen')->sole();
        $checklist = Checklist::query()->findOrFail($task->checklist_id);
        $this->assertSame('KI-Aufgaben', $checklist->name);
        $this->assertNull($checklist->project_id);
        $this->assertSame($user->id, (int) $checklist->user_id);
        $this->assertSame('2026-11-05', CarbonImmutable::parse($task->deadline)->toDateString());
        $this->assertEqualsCanonicalizing([$user->id, $colleague->id], $task->task_users()->pluck('users.id')->all());
        $this->assertSame([$task->id], $result['ids']);

        $this->revert($user, $result['batch_id']);
        $this->assertTrue(Task::withTrashed()->findOrFail($task->id)->trashed());
        $this->assertTrue(Checklist::withTrashed()->findOrFail($checklist->id)->trashed());
        $this->assertContains('create_task', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    #[Test]
    public function a_project_task_uses_the_first_editable_checklist_and_respects_visibility(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id);
        $shared = Checklist::factory()->create(['project_id' => $this->project->id, 'name' => 'Technik', 'private' => false, 'user_id' => null]);
        $shared->users()->attach($user->id);
        $hidden = Checklist::factory()->create(['project_id' => null, 'user_id' => User::factory()->create()->id, 'private' => true]);
        $args = ['name' => 'Technikplan schicken', 'project_id' => $this->project->id];
        $this->actingAsToken($user);

        $this->postJson('/api/v1/ai/tasks', ['name' => 'X', 'checklist_id' => $hidden->id])->assertNotFound();
        $this->postJson('/api/v1/ai/tasks', ['name' => 'X', 'project_id' => 999999])->assertNotFound();
        $this->postJson('/api/v1/ai/tasks', ['name' => 'X', 'checklist_id' => $shared->id, 'project_id' => $this->project->id])
            ->assertUnprocessable();
        $preview = $this->postJson('/api/v1/ai/tasks', $args)->assertOk();
        $this->assertSame([], $preview->json('items.0.conflicts'));
        $this->assertStringContainsString('Technik', $preview->json('items.0.label'));

        $this->execute($user, 'POST', '/api/v1/ai/tasks', 'create_task', $args)->assertOk();
        $this->assertSame($shared->id, (int) Task::query()->where('name', 'Technikplan schicken')->sole()->checklist_id);
    }

    // --- S12 Termine gesammelt verschieben ------------------------------------------------------

    private function editor(): User
    {
        return $this->grant(User::factory()->create(), PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
    }

    #[Test]
    public function events_are_shifted_together_with_collisions_in_the_target_room(): void
    {
        $editor = $this->editor();
        $stage = Room::factory()->create(['name' => 'Buehne']);
        $first = $this->makeEvent('Probe A', self::DAY . ' 10:00:00', self::DAY . ' 12:00:00');
        $second = $this->makeEvent('Probe B', self::DAY . ' 14:00:00', self::DAY . ' 16:00:00');
        $this->makeEvent('Belegt', self::DAY . ' 16:30:00', self::DAY . ' 18:00:00', Project::factory()->create(), $stage);
        $this->shift(self::DAY, '13:00', '17:00', 1, null, $second);
        $args = ['event_ids' => [$first->id, $second->id], 'shift_minutes' => 60, 'room_id' => $stage->id];
        $this->actingAsToken($editor);

        $preview = $this->patchJson('/api/v1/ai/events/batch', $args)->assertOk();
        $this->assertSame('update_events', $preview->json('tool'));
        $items = collect($preview->json('items'))->keyBy('id');
        $this->assertSame('2026-11-03T11:00:00+01:00', $items[$first->id]['start']);
        $this->assertStringContainsString('03.11.2026 10:00–12:00 → 03.11.2026 11:00–13:00', $items[$first->id]['label']);
        $this->assertSame($stage->id, $items[$first->id]['room']['id']);
        $this->assertSame([], $items[$first->id]['conflicts']);
        $types = array_column($items[$second->id]['conflicts'], 'type');
        $this->assertContains('room_collision', $types);
        $this->assertContains('shifts_not_moved', $types);
        $this->assertSame('10:00', $first->fresh()->start_time->format('H:i'));

        $result = $this->execute($editor, 'PATCH', '/api/v1/ai/events/batch', 'update_events', $args)->assertOk()->json();
        $this->assertSame('11:00', $first->fresh()->start_time->format('H:i'));
        $this->assertSame('17:00', $second->fresh()->end_time->format('H:i'));
        $this->assertSame($stage->id, (int) $second->fresh()->room_id);

        $this->revert($editor, $result['batch_id']);
        $this->assertSame('10:00', $first->fresh()->start_time->format('H:i'));
        $this->assertSame($this->room->id, (int) $second->fresh()->room_id);
        $this->assertContains('update_events', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    #[Test]
    public function moving_events_to_another_day_moves_their_shift_dates_and_the_undo_moves_them_back(): void
    {
        $editor = $this->editor();
        $shift = $this->shift(self::DAY, '18:00', '23:00');
        $args = ['event_ids' => [$this->event->id], 'move_to_day' => '2026-11-06'];
        $this->actingAsToken($editor);

        $preview = $this->patchJson('/api/v1/ai/events/batch', $args)->assertOk();
        $this->assertSame('2026-11-06T19:00:00+01:00', $preview->json('items.0.start'));
        $this->assertSame('shifts_moved', $preview->json('items.0.conflicts.0.type'));
        $this->assertSame(self::DAY, CarbonImmutable::parse($shift->fresh()->start_date)->toDateString());

        $result = $this->execute($editor, 'PATCH', '/api/v1/ai/events/batch', 'update_events', $args)->assertOk()->json();
        $this->assertSame('2026-11-06 19:00', $this->event->fresh()->start_time->format('Y-m-d H:i'));
        $this->assertSame('2026-11-06', CarbonImmutable::parse($shift->fresh()->start_date)->toDateString());

        $this->revert($editor, $result['batch_id']);
        $this->assertSame(self::DAY . ' 19:00', $this->event->fresh()->start_time->format('Y-m-d H:i'));
        $this->assertSame(self::DAY, CarbonImmutable::parse($shift->fresh()->start_date)->toDateString());
    }

    #[Test]
    public function batch_moves_need_update_rights_whole_days_for_all_day_events_and_one_change(): void
    {
        $allDay = $this->makeEvent('Feiertag', self::DAY . ' 00:00:00', self::DAY . ' 23:59:00');
        $allDay->forceFill(['allDay' => true])->save();
        $stranger = User::factory()->create();

        $this->actingAsToken($stranger);
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [$this->event->id], 'shift_minutes' => 30])
            ->assertOk()
            ->assertJsonPath('items.0.conflicts.0.type', 'missing_permission')
            ->assertJsonPath('summary.blocked', 1);
        $this->execute($stranger, 'PATCH', '/api/v1/ai/events/batch', 'update_events', [
            'event_ids' => [$this->event->id], 'shift_minutes' => 30,
        ])->assertForbidden();

        $editor = $this->editor();
        $this->actingAsToken($editor);
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [999999], 'shift_minutes' => 30])->assertNotFound();
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [$this->event->id]])->assertUnprocessable();
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [$this->event->id], 'shift_minutes' => 20000])
            ->assertUnprocessable();
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [$allDay->id], 'shift_minutes' => 90])
            ->assertOk()
            ->assertJsonPath('items.0.conflicts.0.type', 'invalid_move');
        $this->patchJson('/api/v1/ai/events/batch', ['event_ids' => [$allDay->id], 'shift_minutes' => 1440])
            ->assertOk()
            ->assertJsonPath('items.0.start', '2026-11-04T00:00:00+01:00');
    }

    // --- S13 Schichten anlegen und kopieren -----------------------------------------------------

    #[Test]
    public function shifts_are_created_for_an_event_and_the_undo_deletes_them_while_nobody_is_assigned(): void
    {
        $planner = $this->planner();
        $helper = ShiftQualification::factory()->create(['name' => 'Helfer']);
        $args = ['event_id' => $this->event->id, 'shifts' => [
            ['craft_id' => $this->craft->id, 'start' => '17:00', 'end' => '23:30', 'qualifications' => [
                ['qualification_id' => $this->qualification->id, 'value' => 1],
                ['qualification_id' => $helper->id, 'value' => 2],
            ]],
            ['craft_id' => $this->craft->id, 'start' => '22:00', 'end' => '02:00', 'break_minutes' => 0, 'description' => 'Abbau',
                'qualifications' => [['qualification_id' => $this->qualification->id, 'value' => 1]]],
        ]];
        $this->actingAsToken($planner);
        $count = Shift::query()->where('event_id', $this->event->id)->count();

        $preview = $this->postJson('/api/v1/ai/shifts', $args)->assertOk();
        $this->assertSame('create_shifts', $preview->json('tool'));
        $this->assertSame('Ton · Vorstellung (1× Meister, 2× Helfer)', $preview->json('items.0.label'));
        $this->assertSame('2026-11-03T17:00:00+01:00', $preview->json('items.0.start'));
        $this->assertSame('2026-11-04T02:00:00+01:00', $preview->json('items.1.end'));
        $this->assertSame($count, Shift::query()->where('event_id', $this->event->id)->count());

        $result = $this->execute($planner, 'POST', '/api/v1/ai/shifts', 'create_shifts', $args)->assertOk()->json();
        $this->assertCount(2, $result['ids']);
        $first = Shift::query()->findOrFail($result['ids'][0]);
        // Ohne Angabe die gesetzliche Mindestpause (6,5 h → 30 min), mit Angabe der Wert.
        $this->assertSame(30, (int) $first->break_minutes);
        $this->assertSame([1, 2], $first->shiftsQualifications()->orderBy('value')->pluck('value')->map(fn ($v): int => (int) $v)->all());
        $second = Shift::query()->findOrFail($result['ids'][1]);
        $this->assertSame('2026-11-04', CarbonImmutable::parse($second->end_date)->toDateString());
        $this->assertSame('Abbau', $second->description);
        $this->assertSame(0, (int) $second->break_minutes);

        // Jemand wird eingetragen → Undo verweigert (409), Schichten bleiben.
        $this->assign($first, $this->worker('Spaeter'));
        $this->revert($planner, $result['batch_id'], 409);
        $this->assertNotNull(Shift::query()->find($first->id));
        $this->assertContains('create_shifts', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }

    #[Test]
    public function creating_shifts_needs_the_planner_right_and_the_craft_scope(): void
    {
        $foreign = Craft::factory()->create(['name' => 'Licht', 'assignable_by_all' => false]);
        $args = ['event_id' => $this->event->id, 'shifts' => [
            ['craft_id' => $foreign->id, 'start' => '18:00', 'end' => '20:00',
                'qualifications' => [['qualification_id' => $this->qualification->id, 'value' => 1]]],
        ]];

        $this->actingAsToken(User::factory()->create());
        $this->postJson('/api/v1/ai/shifts', $args)->assertForbidden();

        $planner = $this->planner();
        $this->actingAsToken($planner);
        $this->postJson('/api/v1/ai/shifts', array_merge($args, ['event_id' => 999999]))->assertNotFound();
        $this->postJson('/api/v1/ai/shifts', ['event_id' => $this->event->id, 'shifts' => [
            ['craft_id' => $this->craft->id, 'start' => '18:00', 'end' => '20:00', 'qualifications' => []],
        ]])->assertUnprocessable();
        $this->postJson('/api/v1/ai/shifts', $args)
            ->assertOk()
            ->assertJsonPath('items.0.conflicts.0.type', 'missing_permission');
        $this->execute($planner, 'POST', '/api/v1/ai/shifts', 'create_shifts', $args)->assertForbidden();
    }

    #[Test]
    public function copying_shifts_keeps_relative_times_and_skips_assignments_with_violations(): void
    {
        $planner = $this->planner();
        $fresh = $this->worker('Frisch');
        $tired = $this->worker('Muede', ['maxWorkingHoursOnDay' => 10.0]);
        $source = $this->shift(self::DAY, '17:00', '23:00', 2);
        $this->assign($source, $fresh);
        $this->assign($source, $tired);
        // Zieltermin zwei Tage spaeter, eine Stunde frueher; Muede arbeitet dort schon 06–14.
        $target = $this->makeEvent('Gastspiel', '2026-11-05 18:00:00', '2026-11-05 21:00:00');
        $busy = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'craft_id' => $this->craft->id, 'start_date' => '2026-11-05', 'end_date' => '2026-11-05',
            'start' => '06:00', 'end' => '14:00', 'break_minutes' => 0, 'is_committed' => false,
        ]));
        $this->assign($busy, $tired);
        $args = ['source_event_id' => $this->event->id, 'target_event_id' => $target->id, 'with_assignments' => true];
        $this->actingAsToken($planner);
        $before = [Shift::query()->count(), ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()];

        $preview = $this->postJson('/api/v1/ai/shifts/copy', $args)->assertOk();
        $this->assertSame('copy_shifts', $preview->json('tool'));
        $this->assertSame('2026-11-05T16:00:00+01:00', $preview->json('items.0.start'));
        $this->assertSame('2026-11-05T22:00:00+01:00', $preview->json('items.0.end'));
        $assignments = collect($preview->json('items'))->filter(fn (array $item): bool => $item['person'] !== null)->keyBy('person.id');
        $this->assertSame([], $assignments[$fresh->id]['conflicts']);
        $this->assertSame('assignment_skipped', $assignments[$tired->id]['conflicts'][0]['type']);
        $this->assertSame('rule:maxWorkingHoursOnDay', $assignments[$tired->id]['conflicts'][1]['type']);
        $this->assertSame(0, $preview->json('summary.blocked'));
        $this->assertSame($before, [Shift::query()->count(), ShiftWorker::withTrashed()->count(), ShiftRuleViolation::query()->count()]);

        $result = $this->execute($planner, 'POST', '/api/v1/ai/shifts/copy', 'copy_shifts', $args)->assertOk()->json();
        $copy = Shift::query()->where('event_id', $target->id)->sole();
        $this->assertSame('16:00', CarbonImmutable::parse($copy->start)->format('H:i'));
        $this->assertSame(2, (int) $copy->shiftsQualifications()->sum('value'));
        $this->assertSame([$fresh->id], $this->workerIds($copy));

        $this->revert($planner, $result['batch_id']);
        $this->assertNull(Shift::query()->find($copy->id));
        $this->assertSame([$fresh->id, $tired->id], $this->workerIds($source));
        $this->assertContains('copy_shifts', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }
}

