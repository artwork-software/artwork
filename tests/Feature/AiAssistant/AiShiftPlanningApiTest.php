<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Http\Requests\StoreAiConversationTurnRequest;
use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
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
 * ai-shift-planning (Core 1–6 + Erweiterung 1/2): Personen, Auslastung, Vorschlag ohne Speichern,
 * Zuweisung mit Vorschau/Ausfuehrung/Undo, Schichten eines Termins.
 */
final class AiShiftPlanningApiTest extends TestCase
{
    use GrantsAiAccess;

    use CreatesShiftRuleFixtures;

    /** Montag der ISO-Woche 45/2026. */
    private const MONDAY = '2026-11-02';

    private Craft $craft;

    private ShiftQualification $qualification;

    private Room $room;

    private Event $event;

    private \Laravel\Passport\Client $aiClient;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $this->aiClient = Passport::client()->newQuery()->forceCreate([
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
            'ai_session_client_id' => (string) $this->aiClient->getKey(),
        ]);

        // assignable_by_all: jede planende Person darf das Gewerk planen (CraftScopeService).
        $this->craft = Craft::factory()->create(['name' => 'Ton', 'abbreviation' => 'TON', 'assignable_by_all' => true]);
        $this->qualification = ShiftQualification::factory()->create(['name' => 'Meister']);
        $this->room = Room::factory()->create(['name' => 'Studio 1']);
        $this->event = Event::factory()->create([
            'room_id' => $this->room->id,
            'eventName' => 'Vorstellung',
            'start_time' => self::MONDAY . ' 08:00:00',
            'end_time' => self::MONDAY . ' 22:00:00',
        ]);
    }

    // --- Helfer ---------------------------------------------------------------------------------

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function planner(): User
    {
        $user = User::factory()->create();

        foreach ([PermissionEnum::SHIFT_PLANNER->value, PermissionEnum::VIEW_SHIFT_PLAN->value] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user->fresh();
    }

    /**
     * Person im Gewerk mit der Funktion; mit Vertrag und optional Regeln.
     *
     * @param array<string, float> $rules trigger_type => Wert
     */
    private function worker(array $rules = [], bool $contract = true, string $lastName = 'Planbar'): User
    {
        if ($contract) {
            [$user, $userContract] = $this->userWithContract();
            foreach ($rules as $trigger => $value) {
                $this->ruleForContract($userContract, $trigger, $value, ['name' => $trigger]);
            }
        } else {
            $user = User::factory()->create();
        }

        $user->forceFill(['first_name' => 'Ada', 'last_name' => $lastName, 'can_work_shifts' => true])->save();
        $user->assignedCrafts()->attach($this->craft->id);
        $user->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);

        return $user->fresh();
    }

    private function shift(string $date, string $start, string $end, int $demand = 1, ?string $endDate = null): Shift
    {
        $shift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'event_id' => $this->event->id,
            'craft_id' => $this->craft->id,
            'start_date' => $date,
            'end_date' => $endDate ?? $date,
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
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $this->qualification->id,
            'shift_count' => 1,
        ]);
    }

    /** @param array<string, mixed> $body */
    private function suggest(User $person, array $body = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/ai/shift-plan/suggest', array_merge([
            'person' => ['type' => 'user', 'id' => $person->id],
            'from' => self::MONDAY,
            'to' => '2026-11-15',
        ], $body));
    }

    /** @return array{shift_workers: int, violations: int, shifts: int} */
    private function counts(): array
    {
        return [
            'shift_workers' => ShiftWorker::withTrashed()->count(),
            'violations' => ShiftRuleViolation::query()->count(),
            'shifts' => Shift::withTrashed()->count(),
        ];
    }

    /** @param array<string, mixed> $args */
    private function confirmedAction(User $user, array $args, string $tool = 'assign_shifts'): AiConversationTurn
    {
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Schichten',
            'status' => AiConversation::STATUS_AWAITING_CONFIRMATION,
        ]);

        return $conversation->turns()->create([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Plane die Schichten',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'tool_calls' => [],
            'context' => [],
            'pending_action' => [
                'tool' => $tool,
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => 'Schichten zuweisen', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    private function writeToken(User $user, AiConversationTurn $turn): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->actingAs($this->grantAiAccess($user))->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk()->json('access_token');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    // --- Personen ------------------------------------------------------------------------------

    #[Test]
    public function people_are_found_by_name_with_crafts_qualifications_and_contract(): void
    {
        $person = $this->worker();
        $freelancer = Freelancer::factory()->create([
            'first_name' => 'Ada', 'last_name' => 'Frei', 'can_work_shifts' => true,
        ]);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Anders', 'can_work_shifts' => true]);
        $this->actingAsToken($this->planner());

        $response = $this->getJson('/api/v1/ai/people?q=ada')->assertOk();

        $this->assertSame(2, $response->json('total'));
        $user = collect($response->json('data'))->firstWhere('type', 'user');
        $this->assertSame($person->id, $user['id']);
        $this->assertSame('Ada Planbar', $user['name']);
        $this->assertTrue($user['has_contract']);
        $this->assertSame([['id' => $this->craft->id, 'name' => 'Ton']], $user['crafts']);
        $this->assertSame([['id' => $this->qualification->id, 'name' => 'Meister']], $user['qualifications']);
        $this->assertSame($freelancer->id, collect($response->json('data'))->firstWhere('type', 'freelancer')['id']);
    }

    #[Test]
    public function people_and_planning_need_the_plan_shifts_permission(): void
    {
        $person = $this->worker();
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/people?q=ada')->assertForbidden();
        $this->getJson("/api/v1/ai/people/user/{$person->id}/workload?from=" . self::MONDAY . '&to=2026-11-08')
            ->assertForbidden();
        $this->suggest($person)->assertForbidden();
        $this->postJson('/api/v1/ai/shift-assignments', [
            'person' => ['type' => 'user', 'id' => $person->id],
            'assignments' => [['shift_id' => 1, 'shift_qualification_id' => 1]],
        ])->assertForbidden();
    }

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $this->actingAsToken($this->planner(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/people')->assertForbidden();
    }

    // --- Auslastung ----------------------------------------------------------------------------

    #[Test]
    public function the_workload_reports_effective_minutes_rules_absences_and_remaining_capacity(): void
    {
        $person = $this->worker(['weeklyMaxHours' => 40.0, 'maxWorkingHoursOnDay' => 10.0]);
        $monday = $this->shift(self::MONDAY, '08:00', '16:30');
        $monday->update(['break_minutes' => 30]);
        $this->assign($monday, $person);
        // Mitternachtsschicht So → Mo: 22:00–02:00 = 120 min in W45 (So) + 120 min in W46 (Mo)
        $night = $this->shift('2026-11-08', '22:00', '02:00', 1, '2026-11-09');
        $this->assign($night, $person);
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $person->id,
            'date' => '2026-11-04', 'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
        ]);
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $person->id,
            'date' => '2026-11-05', 'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
        ]);
        $this->actingAsToken($this->planner());

        $response = $this->getJson("/api/v1/ai/people/user/{$person->id}/workload?from=" . self::MONDAY . '&to=2026-11-09')
            ->assertOk();

        $this->assertSame([
            ['week' => '2026-W45', 'minutes' => 600],
            ['week' => '2026-W46', 'minutes' => 120],
        ], $response->json('planned.weeks'));
        $this->assertSame([['month' => '2026-11', 'minutes' => 720]], $response->json('planned.months'));
        $this->assertEqualsCanonicalizing(
            ['weeklyMaxHours', 'maxWorkingHoursOnDay'],
            array_column($response->json('rules'), 'trigger_type')
        );
        $this->assertNotNull($response->json('contract.name'));
        $this->assertCount(1, $response->json('absences'));
        $this->assertSame('OFF_WORK', $response->json('absences.0.type'));
        $this->assertStringStartsWith('2026-11-04T00:00', $response->json('absences.0.from'));
        $this->assertStringStartsWith('2026-11-05T23:59', $response->json('absences.0.to'));
        $this->assertSame([
            'week' => '2026-W45',
            'minutes_planned' => 600,
            'minutes_max' => 2400,
            'minutes_remaining' => 1800,
            'limited_by' => 'weeklyMaxHours',
        ], $response->json('remaining.weeks.0'));
    }

    #[Test]
    public function the_workload_range_is_limited_to_92_days(): void
    {
        $person = $this->worker();
        $this->actingAsToken($this->planner());

        $this->getJson("/api/v1/ai/people/user/{$person->id}/workload?from=2026-11-01&to=2027-03-01")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
        $this->getJson('/api/v1/ai/people/user/999999/workload?from=2026-11-01&to=2026-11-02')->assertNotFound();
    }

    #[Test]
    public function a_freelancer_has_no_target_and_no_rules(): void
    {
        $freelancer = Freelancer::factory()->create(['can_work_shifts' => true]);
        $this->actingAsToken($this->planner());

        $response = $this->getJson("/api/v1/ai/people/freelancer/{$freelancer->id}/workload?from=" . self::MONDAY . '&to=2026-11-08')
            ->assertOk();

        $this->assertNull($response->json('target_minutes_by_week'));
        $this->assertNull($response->json('contract'));
        $this->assertSame([], $response->json('rules'));
        $this->assertNull($response->json('remaining.weeks.0.minutes_max'));
    }

    // --- Vorschlag -----------------------------------------------------------------------------

    #[Test]
    public function the_suggestion_respects_the_weekly_cap_including_already_planned_minutes(): void
    {
        $person = $this->worker([], false);
        $this->assign($this->shift(self::MONDAY, '08:00', '16:00'), $person); // 480 geplant
        $tuesday = $this->shift('2026-11-03', '08:00', '16:00');
        $wednesday = $this->shift('2026-11-04', '08:00', '16:00');
        $thursday = $this->shift('2026-11-05', '08:00', '16:00');
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->suggest($person, ['to' => '2026-11-08', 'max_minutes_per_week' => 1500])->assertOk();

        $this->assertSame([$tuesday->id, $wednesday->id], array_column($response->json('picked'), 'shift_id'));
        $this->assertSame(480, $response->json('picked.0.minutes'));
        $this->assertSame($this->qualification->id, $response->json('picked.0.shift_qualification_id'));
        $this->assertSame(['id' => $this->event->id, 'name' => 'Vorstellung'], $response->json('picked.0.event'));
        $this->assertSame($thursday->id, $response->json('skipped.0.shift_id'));
        $this->assertSame('limit_week', $response->json('skipped.0.reason_code'));
        $this->assertSame(
            ['week' => '2026-W45', 'minutes_before' => 480, 'minutes_after' => 1440],
            $response->json('totals.weeks.0')
        );
        $this->assertFalse($response->json('limits_applied.contract_rules'));
        $this->assertSame($before, $this->counts());
    }

    #[Test]
    public function the_suggestion_respects_the_monthly_cap_and_excluded_weeks(): void
    {
        $person = $this->worker([], false);
        $this->assign($this->shift('2026-11-02', '08:00', '16:00'), $person); // 480 im November
        $w45 = $this->shift('2026-11-03', '08:00', '16:00');
        $w46 = $this->shift('2026-11-10', '08:00', '16:00');
        $w47 = $this->shift('2026-11-17', '08:00', '16:00');
        $this->actingAsToken($this->planner());

        $response = $this->suggest($person, [
            'to' => '2026-11-22',
            'max_minutes_per_month' => 1000,
            'exclude_weeks' => ['2026-W46'],
        ])->assertOk();

        $this->assertSame([$w45->id], array_column($response->json('picked'), 'shift_id'));
        $skipped = collect($response->json('skipped'))->keyBy('shift_id');
        $this->assertSame('excluded', $skipped[$w46->id]['reason_code']);
        $this->assertSame('limit_month', $skipped[$w47->id]['reason_code']);
    }

    #[Test]
    public function the_suggestion_respects_daily_maximum_and_rest_time_and_writes_nothing(): void
    {
        $person = $this->worker(['maxWorkingHoursOnDay' => 10.0, 'restTimeBeforeWorkday' => 11.0]);
        $this->assign($this->shift(self::MONDAY, '06:00', '14:00'), $person);   // 8 h am Montag
        $this->assign($this->shift('2026-11-03', '14:00', '23:00'), $person);   // Di bis 23:00
        $mondayEvening = $this->shift(self::MONDAY, '15:00', '19:00');          // Mo 12 h > 10 h
        $wednesdayEarly = $this->shift('2026-11-04', '06:00', '14:00');         // 7 h Ruhe < 11 h
        $wednesdayLate = $this->shift('2026-11-04', '12:00', '20:00');          // 13 h Ruhe
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->suggest($person, ['to' => '2026-11-08'])->assertOk();

        $this->assertSame([$wednesdayLate->id], array_column($response->json('picked'), 'shift_id'));
        $skipped = collect($response->json('skipped'))->keyBy('shift_id');
        $this->assertSame('rule:maxWorkingHoursOnDay', $skipped[$mondayEvening->id]['reason_code']);
        $this->assertSame('rule:restTimeBeforeWorkday', $skipped[$wednesdayEarly->id]['reason_code']);
        $this->assertTrue($response->json('limits_applied.contract_rules'));
        $this->assertSame($before, $this->counts());
    }

    #[Test]
    public function overlapping_picks_and_absences_are_skipped(): void
    {
        $person = $this->worker([], false);
        $first = $this->shift('2026-11-03', '08:00', '16:00');
        $overlapping = $this->shift('2026-11-03', '12:00', '18:00');
        $onVacation = $this->shift('2026-11-04', '08:00', '16:00');
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $person->id,
            'date' => '2026-11-04', 'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
        ]);
        $this->actingAsToken($this->planner());

        $response = $this->suggest($person, ['to' => '2026-11-08'])->assertOk();

        $this->assertSame([$first->id], array_column($response->json('picked'), 'shift_id'));
        $skipped = collect($response->json('skipped'))->keyBy('shift_id');
        $this->assertSame('overlap', $skipped[$overlapping->id]['reason_code']);
        $this->assertSame('vacation', $skipped[$onVacation->id]['reason_code']);
    }

    #[Test]
    public function fill_none_only_evaluates_new_shifts_and_creates_nothing(): void
    {
        $person = $this->worker([], false);
        $this->shift('2026-11-03', '08:00', '16:00');
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->suggest($person, [
            'fill' => 'none',
            'new_shifts' => [[
                'event_id' => $this->event->id,
                'craft_id' => $this->craft->id,
                'shift_qualification_id' => $this->qualification->id,
                'start' => '2026-11-06T10:00',
                'end' => '2026-11-06T14:00',
                'break_minutes' => 0,
            ]],
        ])->assertOk();

        $this->assertCount(1, $response->json('picked'));
        $this->assertNull($response->json('picked.0.shift_id'));
        $this->assertSame(0, $response->json('picked.0.new_shift_index'));
        $this->assertSame(240, $response->json('picked.0.minutes'));
        $this->assertSame($before, $this->counts());
    }

    #[Test]
    public function shifts_of_other_crafts_and_without_open_demand_are_not_candidates(): void
    {
        $person = $this->worker([], false);
        $full = $this->shift('2026-11-03', '08:00', '16:00');
        $this->assign($full, User::factory()->create());
        $foreign = $this->shift('2026-11-04', '08:00', '16:00');
        $foreign->update(['craft_id' => Craft::factory()->create(['assignable_by_all' => true])->id]);
        $this->actingAsToken($this->planner());

        $response = $this->suggest($person, ['to' => '2026-11-08'])->assertOk();

        $this->assertSame([], $response->json('picked'));
        $this->assertSame([], $response->json('skipped'));
    }

    // --- Zuweisung -----------------------------------------------------------------------------

    #[Test]
    public function the_assignment_preview_lists_conflicts_and_writes_nothing(): void
    {
        $person = $this->worker(['maxWorkingHoursOnDay' => 10.0]);
        $this->assign($this->shift(self::MONDAY, '06:00', '14:00'), $person);
        $ok = $this->shift('2026-11-03', '08:00', '16:00');
        $tooLong = $this->shift(self::MONDAY, '15:00', '19:00');
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->postJson('/api/v1/ai/shift-assignments', [
            'person' => ['type' => 'user', 'id' => $person->id],
            'assignments' => [
                ['shift_id' => $ok->id, 'shift_qualification_id' => $this->qualification->id],
                ['shift_id' => $tooLong->id, 'shift_qualification_id' => $this->qualification->id],
            ],
        ])->assertOk();

        $this->assertTrue($response->json('dry_run'));
        $this->assertSame('assign_shifts', $response->json('tool'));
        $items = collect($response->json('items'))->keyBy('id');
        $this->assertSame([], $items[$ok->id]['conflicts']);
        $this->assertSame('create', $items[$ok->id]['action']);
        $this->assertSame('Ton · Vorstellung (Meister)', $items[$ok->id]['label']);
        $this->assertSame('rule:maxWorkingHoursOnDay', $items[$tooLong->id]['conflicts'][0]['type']);
        $this->assertSame(1, $response->json('summary.with_conflicts'));
        $this->assertSame($before, $this->counts());
    }

    #[Test]
    public function a_confirmed_assignment_is_executed_and_can_be_undone(): void
    {
        $planner = $this->planner();
        $person = $this->worker(['maxWorkingHoursOnDay' => 10.0]);
        $shift = $this->shift('2026-11-03', '08:00', '16:00');
        $args = [
            'person' => ['type' => 'user', 'id' => $person->id],
            'assignments' => [['shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id]],
            'new_shifts' => [[
                'event_id' => $this->event->id,
                'craft_id' => $this->craft->id,
                'shift_qualification_id' => $this->qualification->id,
                'start' => '2026-11-05T10:00',
                'end' => '2026-11-05T14:00',
                'break_minutes' => 0,
            ]],
        ];
        $turn = $this->confirmedAction($planner, $args);
        $this->writeToken($planner, $turn);

        $result = $this->postJson('/api/v1/ai/shift-assignments', array_merge($args, [
            'dry_run' => false,
            'action_id' => $turn->id,
        ]))->assertOk()->json();

        $this->assertFalse($result['dry_run']);
        $this->assertNotNull($result['batch_id']);
        $this->assertCount(2, $result['ids']);
        $this->assertTrue($shift->users()->whereKey($person->id)->exists());
        $created = Shift::query()->findOrFail($result['ids'][1]);
        $this->assertSame($this->event->id, $created->event_id);
        $this->assertTrue($created->users()->whereKey($person->id)->exists());
        $this->assertSame(AiActionBatch::STATUS_EXECUTED, AiActionBatch::query()->find($result['batch_id'])->status);

        $this->app['auth']->forgetGuards();
        $this->withoutHeader('Authorization')
            ->actingAs($planner)
            ->postJson('/ai/actions/batches/' . $result['batch_id'] . '/revert')
            ->assertOk();

        $this->assertFalse($shift->users()->whereKey($person->id)->exists());
        $this->assertSoftDeleted('shifts', ['id' => $created->id]);
        $this->assertSame(0, ShiftWorker::query()
            ->where('employable_type', User::class)->where('employable_id', $person->id)->count());
    }

    #[Test]
    public function the_execution_refuses_a_rule_violation_and_writes_nothing(): void
    {
        $planner = $this->planner();
        $person = $this->worker(['maxWorkingHoursOnDay' => 10.0]);
        $this->assign($this->shift(self::MONDAY, '06:00', '14:00'), $person);
        $tooLong = $this->shift(self::MONDAY, '15:00', '19:00');
        $args = [
            'person' => ['type' => 'user', 'id' => $person->id],
            'assignments' => [['shift_id' => $tooLong->id, 'shift_qualification_id' => $this->qualification->id]],
        ];
        $turn = $this->confirmedAction($planner, $args);
        $this->writeToken($planner, $turn);
        $before = $this->counts();

        $this->postJson('/api/v1/ai/shift-assignments', array_merge($args, [
            'dry_run' => false,
            'action_id' => $turn->id,
        ]))->assertUnprocessable();

        $this->assertSame($before['shift_workers'], $this->counts()['shift_workers']);
        $this->assertFalse($tooLong->users()->whereKey($person->id)->exists());
    }

    #[Test]
    public function execution_without_an_action_is_rejected(): void
    {
        $person = $this->worker([], false);
        $shift = $this->shift('2026-11-03', '08:00', '16:00');
        $this->actingAsToken($this->planner(), [AiScopes::READ]);

        $this->postJson('/api/v1/ai/shift-assignments', [
            'person' => ['type' => 'user', 'id' => $person->id],
            'assignments' => [['shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id]],
            'dry_run' => false,
            'action_id' => (string) Str::uuid(),
        ])->assertForbidden();
    }

    #[Test]
    public function assign_shifts_is_a_confirmable_tool(): void
    {
        $this->assertContains('assign_shifts', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);

        $user = $this->planner();
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id, 'title' => 'Schichten', 'status' => AiConversation::STATUS_ACTIVE,
        ]);
        $args = ['person' => ['type' => 'user', 'id' => 1], 'assignments' => [['shift_id' => 2, 'shift_qualification_id' => 3]]];
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', [
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Plane Ada ein',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'pending_action' => [
                'tool' => 'assign_shifts',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => '1 Schicht zuweisen', 'items' => [['action' => 'create', 'label' => 'Ton']]],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ])->assertCreated();
    }

    // --- Schichten eines Termins (Erweiterung 2) ----------------------------------------------

    #[Test]
    public function the_shifts_of_an_event_report_their_staffing(): void
    {
        $person = $this->worker([], false);
        $staffed = $this->shift(self::MONDAY, '08:00', '16:00', 1);
        $this->assign($staffed, $person);
        $this->shift(self::MONDAY, '16:00', '22:00', 2);
        $this->actingAsToken($this->planner());

        $response = $this->getJson("/api/v1/ai/events/{$this->event->id}/shifts")->assertOk();

        $this->assertSame('Vorstellung', $response->json('event.name'));
        $this->assertSame(['id' => $this->room->id, 'name' => 'Studio 1'], $response->json('event.room'));
        $this->assertCount(2, $response->json('shifts'));
        $this->assertSame(
            ['id' => $this->qualification->id, 'name' => 'Meister', 'required' => 1, 'staffed' => 1, 'open' => 0],
            $response->json('shifts.0.qualifications.0')
        );
        $this->assertSame([['name' => 'Ada Planbar', 'qualification' => 'Meister']], $response->json('shifts.0.assignees'));
        $this->assertSame(
            ['shifts' => 2, 'required' => 3, 'staffed' => 1, 'open' => 2, 'fully_staffed' => false],
            $response->json('summary')
        );
    }

    #[Test]
    public function the_shifts_of_an_event_need_the_shift_plan_permission(): void
    {
        $this->actingAsToken(User::factory()->create());
        $this->getJson("/api/v1/ai/events/{$this->event->id}/shifts")->assertForbidden();

        $this->actingAsToken($this->planner());
        $this->getJson('/api/v1/ai/events/999999/shifts')->assertNotFound();
    }

    // --- Mehrtaegige Termine (Erweiterung 3) ---------------------------------------------------

    #[Test]
    public function end_day_makes_an_event_multi_day_and_hints_at_unchanged_shifts(): void
    {
        $this->shift(self::MONDAY, '08:00', '16:00');
        $this->actingAsToken($this->adminUser());
        $before = Event::query()->findOrFail($this->event->id)->end_time->toDateTimeString();

        $response = $this->patchJson("/api/v1/ai/events/{$this->event->id}", ['end_day' => '2026-11-04'])
            ->assertOk();

        $this->assertStringStartsWith('2026-11-04T22:00', $response->json('items.0.end'));
        $this->assertStringStartsWith('2026-11-02T08:00', $response->json('items.0.start'));
        $this->assertContains('shifts_not_extended', array_column($response->json('items.0.conflicts'), 'type'));
        $this->assertSame($before, Event::query()->findOrFail($this->event->id)->end_time->toDateTimeString());
    }

    #[Test]
    public function end_day_before_the_start_is_rejected(): void
    {
        $this->actingAsToken($this->adminUser());

        $this->patchJson("/api/v1/ai/events/{$this->event->id}", ['end_day' => '2026-11-01'])->assertUnprocessable();
        $this->patchJson("/api/v1/ai/events/{$this->event->id}", ['end_day' => '04.11.2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_day');
    }

    #[Test]
    public function a_multi_day_event_keeps_its_length_on_an_unrelated_change(): void
    {
        $this->event->update(['end_time' => '2026-11-04 22:00:00']);
        $this->actingAsToken($this->adminUser());

        $response = $this->patchJson("/api/v1/ai/events/{$this->event->id}", ['name' => 'Gastspiel'])->assertOk();

        $this->assertStringStartsWith('2026-11-04T22:00', $response->json('items.0.end'));
        $this->assertSame([], $response->json('items.0.conflicts'));
    }

    // --- Erweiterung 2 A: offene Schichten -----------------------------------------------------

    #[Test]
    public function open_shifts_list_the_missing_people_per_qualification(): void
    {
        $person = $this->worker([], false);
        $open = $this->shift('2026-11-03', '08:00', '16:00', 3);
        $this->assign($open, $person);
        $full = $this->shift('2026-11-04', '08:00', '16:00', 1);
        $this->assign($full, User::factory()->create());
        $this->shift('2026-12-01', '08:00', '16:00', 2); // ausserhalb
        $this->actingAsToken($this->planner());

        $response = $this->getJson('/api/v1/ai/shifts/open?from=' . self::MONDAY . '&to=2026-11-08')->assertOk();

        $this->assertSame(1, $response->json('total'));
        $this->assertSame($open->id, $response->json('data.0.id'));
        $this->assertSame(
            [['qualification' => ['id' => $this->qualification->id, 'name' => 'Meister'], 'missing' => 2]],
            $response->json('data.0.open')
        );
        $this->assertSame(['id' => $this->event->id, 'name' => 'Vorstellung'], $response->json('data.0.event'));
        $this->assertSame(['shifts' => 1, 'missing' => 2], $response->json('summary'));
    }

    #[Test]
    public function open_shifts_hide_crafts_the_planner_may_not_plan(): void
    {
        $this->shift('2026-11-03', '08:00', '16:00', 2);
        $this->craft->update(['assignable_by_all' => false]);
        $this->actingAsToken($this->planner());

        $this->getJson('/api/v1/ai/shifts/open?from=' . self::MONDAY . '&to=2026-11-08')
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    // --- Erweiterung 2 B: Regelverstoesse ------------------------------------------------------

    #[Test]
    public function rule_violations_are_listed_read_only(): void
    {
        $person = $this->worker(['maxWorkingHoursOnDay' => 8.0]);
        $shift = $this->shift('2026-11-03', '08:00', '18:00');
        $this->assign($shift, $person);
        app(\Artwork\Modules\Shift\Services\ShiftRuleService::class)
            ->validateRulesForUser($person, \Carbon\Carbon::parse('2026-11-03'), \Carbon\Carbon::parse('2026-11-03'));
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->getJson('/api/v1/ai/shift-rule-violations?from=' . self::MONDAY . '&to=2026-11-08')
            ->assertOk();

        $this->assertSame(1, $response->json('total'));
        $this->assertSame(['type' => 'user', 'id' => $person->id, 'name' => 'Ada Planbar'], $response->json('data.0.person'));
        $this->assertSame('maxWorkingHoursOnDay', $response->json('data.0.rule.trigger_type'));
        $this->assertSame('2026-11-03', $response->json('data.0.date'));
        $this->assertSame('active', $response->json('data.0.status'));
        $this->assertStringContainsString('planned_hours', (string) $response->json('data.0.detail'));
        $this->assertSame($before, $this->counts());
        $this->getJson('/api/v1/ai/shift-rule-violations?from=' . self::MONDAY . '&to=2026-11-08&status=resolved')
            ->assertOk()->assertJsonPath('total', 0);
    }

    // --- Erweiterung 2 C: Kandidat:innen -------------------------------------------------------

    #[Test]
    public function candidates_are_the_people_who_could_take_the_shift_without_a_violation(): void
    {
        $free = $this->worker([], false, 'Frei');
        $tired = $this->worker(['maxWorkingHoursOnDay' => 10.0], true, 'Muede');
        $this->assign($this->shift('2026-11-03', '06:00', '14:00'), $tired);
        $assigned = $this->worker([], false, 'Drin');
        $target = $this->shift('2026-11-03', '15:00', '19:00', 2);
        $this->assign($target, $assigned);
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->getJson("/api/v1/ai/shifts/{$target->id}/candidates")->assertOk();

        $this->assertSame($this->qualification->id, $response->json('shift.qualification.id'));
        $this->assertSame(1, $response->json('shift.open'));
        $this->assertSame([$free->id], array_column(array_column($response->json('data'), 'person'), 'id'));
        $this->assertSame(0, $response->json('data.0.minutes_planned_week'));
        $this->assertSame($tired->id, $response->json('rejected.0.person.id'));
        $this->assertSame('rule:maxWorkingHoursOnDay', $response->json('rejected.0.reason_code'));
        $this->assertSame($before, $this->counts());
    }

    // --- Erweiterung 2 D: Gewerke und Kapazitaet -----------------------------------------------

    #[Test]
    public function crafts_can_be_looked_up_and_report_the_capacity_of_their_people(): void
    {
        $limited = $this->worker(['weeklyMaxHours' => 10.0], true, 'Begrenzt');
        $this->assign($this->shift('2026-11-03', '08:00', '16:00'), $limited);
        $open = $this->worker([], false, 'Offen');
        $this->actingAsToken($this->planner());

        $this->getJson('/api/v1/ai/crafts?q=to')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->craft->id)
            ->assertJsonPath('data.0.plannable', true);

        $response = $this->getJson("/api/v1/ai/crafts/{$this->craft->id}/capacity?week=2026-W45")->assertOk();

        $this->assertSame('2026-W45', $response->json('week'));
        $rows = collect($response->json('data'))->keyBy('person.id');
        $this->assertSame(480, $rows[$limited->id]['minutes_planned']);
        $this->assertSame(600, $rows[$limited->id]['minutes_max']);
        $this->assertSame(120, $rows[$limited->id]['minutes_remaining']);
        $this->assertSame('weeklyMaxHours', $rows[$limited->id]['limited_by']);
        $this->assertSame(0, $rows[$open->id]['minutes_planned']);
        $this->getJson("/api/v1/ai/crafts/{$this->craft->id}/capacity?week=45")->assertUnprocessable();
    }

    #[Test]
    public function the_capacity_of_a_craft_the_planner_may_not_plan_is_forbidden(): void
    {
        $this->craft->update(['assignable_by_all' => false]);
        $this->actingAsToken($this->planner());

        $this->getJson("/api/v1/ai/crafts/{$this->craft->id}/capacity?week=2026-W45")->assertForbidden();
    }

    // --- Erweiterung 2 E: Austragen ------------------------------------------------------------

    #[Test]
    public function the_unassign_preview_warns_about_open_demand_and_writes_nothing(): void
    {
        $person = $this->worker([], false);
        $shift = $this->shift('2026-11-03', '08:00', '16:00');
        $this->assign($shift, $person);
        $other = $this->shift('2026-11-04', '08:00', '16:00');
        $this->actingAsToken($this->planner());
        $before = $this->counts();

        $response = $this->postJson('/api/v1/ai/shift-assignments/remove', [
            'person' => ['type' => 'user', 'id' => $person->id],
            'shift_ids' => [$shift->id, $other->id],
        ])->assertOk();

        $this->assertSame('unassign_shifts', $response->json('tool'));
        $items = collect($response->json('items'))->keyBy('id');
        $this->assertSame('delete', $items[$shift->id]['action']);
        $this->assertSame(['demand_opens'], array_column($items[$shift->id]['conflicts'], 'type'));
        $this->assertSame(['not_assigned'], array_column($items[$other->id]['conflicts'], 'type'));
        $this->assertSame(1, $response->json('summary.blocked'));
        $this->assertSame($before, $this->counts());
    }

    #[Test]
    public function a_confirmed_unassignment_is_executed_and_can_be_undone(): void
    {
        $planner = $this->planner();
        $person = $this->worker([], false);
        $shift = $this->shift('2026-11-03', '08:00', '16:00');
        $this->assign($shift, $person);
        $args = ['person' => ['type' => 'user', 'id' => $person->id], 'shift_ids' => [$shift->id]];
        $turn = $this->confirmedAction($planner, $args, 'unassign_shifts');
        $this->writeToken($planner, $turn);

        $result = $this->postJson('/api/v1/ai/shift-assignments/remove', array_merge($args, [
            'dry_run' => false,
            'action_id' => $turn->id,
        ]))->assertOk()->json();

        $this->assertFalse($shift->users()->whereKey($person->id)->exists());
        $this->assertNotNull($result['batch_id']);

        $this->app['auth']->forgetGuards();
        $this->withoutHeader('Authorization')
            ->actingAs($planner)
            ->postJson('/ai/actions/batches/' . $result['batch_id'] . '/revert')
            ->assertOk();

        $this->assertTrue($shift->users()->whereKey($person->id)->exists());
        $this->assertSame(
            $this->qualification->id,
            (int) $shift->users()->whereKey($person->id)->first()->pivot->shift_qualification_id
        );
    }

    #[Test]
    public function unassigning_someone_who_is_not_in_the_shift_is_refused(): void
    {
        $planner = $this->planner();
        $person = $this->worker([], false);
        $shift = $this->shift('2026-11-03', '08:00', '16:00');
        $args = ['person' => ['type' => 'user', 'id' => $person->id], 'shift_ids' => [$shift->id]];
        $turn = $this->confirmedAction($planner, $args, 'unassign_shifts');
        $this->writeToken($planner, $turn);

        $this->postJson('/api/v1/ai/shift-assignments/remove', array_merge($args, [
            'dry_run' => false,
            'action_id' => $turn->id,
        ]))->assertUnprocessable();
        $this->assertContains('unassign_shifts', StoreAiConversationTurnRequest::CONFIRMABLE_TOOLS);
    }
}
