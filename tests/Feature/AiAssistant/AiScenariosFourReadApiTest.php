<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationEnum;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * ai-scenarios-4, lesende Teile: gemeinsame freie Zeit (S14), Vertraege (S15), Stundenuebersicht
 * (S16), Raumauslastung (S17), Projektfilter (S18) und die Lookups Checklisten, Funktionen,
 * Projektstatus.
 */
final class AiScenariosFourReadApiTest extends TestCase
{
    use GrantsAiAccess;

    use CreatesShiftRuleFixtures;

    private const DAY = '2026-11-03';

    private Craft $craft;

    private ShiftQualification $qualification;

    private Room $room;

    private Project $project;

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
        $this->qualification = ShiftQualification::factory()->create(['name' => 'Tontechnik']);
        $this->room = Room::factory()->create(['name' => 'Bühne']);
        $this->project = Project::factory()->create(['name' => 'Tosca']);
    }

    // --- Helfer ---------------------------------------------------------------------------------

    private function actingAsToken(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function grant(User $user, string ...$permissions): User
    {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->fresh();
    }

    private function worker(string $lastName): User
    {
        [$user] = $this->userWithContract();
        $user->forceFill(['first_name' => 'Ada', 'last_name' => $lastName, 'can_work_shifts' => true])->save();
        $user->assignedCrafts()->attach($this->craft->id);

        return $user->fresh();
    }

    private function event(string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'room_id' => $this->room->id,
            'project_id' => $this->project->id,
            'start_time' => $start,
            'end_time' => $end,
            'is_planning' => false,
        ], $attributes));
    }

    private function assignShift(User $user, string $start, string $end): void
    {
        $shift = ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'craft_id' => $this->craft->id, 'start_date' => self::DAY, 'end_date' => self::DAY,
            'start' => $start, 'end' => $end, 'break_minutes' => 0, 'is_committed' => false,
            'shift_group_id' => null,
        ]));
        ShiftsQualifications::query()->create([
            'shift_id' => $shift->id, 'shift_qualification_id' => $this->qualification->id, 'value' => 1,
        ]);
        $shift->users()->attach($user->id, ['shift_qualification_id' => $this->qualification->id, 'shift_count' => 1]);
    }

    // --- S14 Gemeinsame freie Zeit ------------------------------------------------------------

    #[Test]
    public function common_free_slots_skip_shifts_absences_and_room_bookings(): void
    {
        // Vergangene Fenster fallen weg; ohne feste Uhr waere der Test ab dem Stichtag rot.
        $this->travelTo(CarbonImmutable::parse(self::DAY)->subDay()->setTime(8, 0));
        $ada = $this->worker('Eins');
        $bob = $this->worker('Zwei');
        $this->assignShift($ada, '08:00', '12:00');
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $bob->id, 'date' => self::DAY,
            'full_day' => false, 'start_time' => '13:00', 'end_time' => '15:00',
            'type' => VacationEnum::NOT_AVAILABLE->value,
        ]);
        Vacation::query()->create([
            'vacationer_type' => User::class, 'vacationer_id' => $bob->id, 'date' => '2026-11-04',
            'full_day' => true, 'type' => VacationEnum::OFF_WORK->value,
        ]);
        $body = [
            'people' => [['type' => 'user', 'id' => $ada->id], ['type' => 'user', 'id' => $bob->id]],
            'from' => self::DAY,
            'to' => '2026-11-04',
            'duration_minutes' => 120,
        ];

        // Nur die eigene Person darf jede:r; fuer andere braucht es Schichtplan-Rechte.
        $this->actingAsToken($ada);
        $this->postJson('/api/v1/ai/people/common-free-slots', $body)
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_shift_plan_access']);

        $this->actingAsToken($this->grant($ada, PermissionEnum::VIEW_SHIFT_PLAN->value));
        $response = $this->postJson('/api/v1/ai/people/common-free-slots', $body)->assertOk();

        // 12–13 ist zu kurz, 15–20 passt; der 04.11. ist fuer Bob ganz belegt.
        $this->assertCount(1, $response->json('slots'));
        $this->assertStringStartsWith(self::DAY . 'T15:00', $response->json('slots.0.start'));
        $this->assertStringStartsWith(self::DAY . 'T20:00', $response->json('slots.0.end'));
        $this->assertSame([$ada->id, $bob->id], array_column($response->json('people'), 'id'));
        $this->assertNull($response->json('room'));

        $this->event(self::DAY . ' 16:00:00', self::DAY . ' 18:00:00');
        $withRoom = $this->postJson('/api/v1/ai/people/common-free-slots', array_merge($body, [
            'room_id' => $this->room->id, 'day_end' => '22:00',
        ]))->assertOk();
        $this->assertSame(['id' => $this->room->id, 'name' => 'Bühne'], $withRoom->json('room'));
        $this->assertCount(1, $withRoom->json('slots'));
        $this->assertStringStartsWith(self::DAY . 'T18:00', $withRoom->json('slots.0.start'));
        $this->assertStringStartsWith(self::DAY . 'T22:00', $withRoom->json('slots.0.end'));
    }

    #[Test]
    public function common_free_slots_validate_people_and_unknown_people_are_not_found(): void
    {
        $ada = $this->worker('Eins');
        $this->actingAsToken($ada);

        $this->postJson('/api/v1/ai/people/common-free-slots', [
            'people' => [['type' => 'user', 'id' => $ada->id]],
            'from' => self::DAY, 'to' => self::DAY, 'duration_minutes' => 60,
        ])->assertUnprocessable()->assertJsonValidationErrors('people');

        $this->postJson('/api/v1/ai/people/common-free-slots', [
            'people' => [['type' => 'user', 'id' => $ada->id], ['type' => 'user', 'id' => 999999999]],
            'from' => self::DAY, 'to' => '2027-03-01', 'duration_minutes' => 60,
        ])->assertUnprocessable()->assertJsonValidationErrors('to');

        $this->postJson('/api/v1/ai/people/common-free-slots', [
            'people' => [['type' => 'user', 'id' => $ada->id], ['type' => 'user', 'id' => 999999999]],
            'from' => self::DAY, 'to' => self::DAY, 'duration_minutes' => 60,
        ])->assertNotFound();
    }

    // --- S15 Vertraege -------------------------------------------------------------------------

    #[Test]
    public function contracts_are_sorted_by_deadline_and_need_contract_rights(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user);
        $today = CarbonImmutable::today();
        $soon = Contract::factory()->create([
            'name' => 'Gastspiel', 'creator_id' => $user->id, 'project_id' => $this->project->id,
            'deadline_date' => $today->addDays(10)->toDateString(), 'amount' => 1500, 'contract_state' => 'offen',
        ]);
        $overdue = Contract::factory()->create([
            'name' => 'Regie', 'creator_id' => $user->id, 'project_id' => null,
            'deadline_date' => $today->subDays(2)->toDateString(),
        ]);
        $without = Contract::factory()->create(['name' => 'Ohne', 'creator_id' => $user->id, 'project_id' => null]);
        $hidden = Contract::factory()->create(['name' => 'Fremd Projekt', 'creator_id' => $user->id]);
        Contract::factory()->create(['name' => 'Fremd', 'deadline_date' => $today->toDateString()]);

        $this->actingAsToken($user);
        $this->getJson('/api/v1/ai/contracts')
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_contract_access']);

        $this->actingAsToken($this->grant($user, PermissionEnum::CONTRACT_SEE_DOWNLOAD->value));
        $response = $this->getJson('/api/v1/ai/contracts')->assertOk();

        $this->assertSame(4, $response->json('total'));
        $this->assertSame([$overdue->id, $soon->id], array_slice(array_column($response->json('data'), 'id'), 0, 2));
        $this->assertTrue($response->json('data.0.overdue'));
        $this->assertSame([
            'id' => $soon->id, 'name' => 'Gastspiel', 'contract_state' => 'offen',
            'deadline' => $today->addDays(10)->toDateString(), 'overdue' => false, 'amount' => '1500.00',
            'currency' => null, 'project' => ['id' => $this->project->id, 'name' => 'Tosca'],
        ], $response->json('data.1'));
        // Ohne Deadline zuletzt; Projekt, das die Person nicht sieht, bleibt anonym.
        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertNull($rows[$hidden->id]['project']);
        $this->assertSame([$hidden->id, $without->id], [$response->json('data.2.id'), $response->json('data.3.id')]);

        $due = $this->getJson('/api/v1/ai/contracts?due_before=' . $today->addDays(14)->toDateString())->assertOk();
        $this->assertSame([$overdue->id, $soon->id], array_column($due->json('data'), 'id'));

        $this->getJson('/api/v1/ai/contracts?project_id=' . $this->project->id . '&q=Gast')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $soon->id);
    }

    // --- S16 Stundenuebersicht -----------------------------------------------------------------

    #[Test]
    public function the_work_time_overview_needs_hour_rights_and_lists_people_with_an_account(): void
    {
        $person = $this->worker('Konto');
        $otherCraft = Craft::factory()->create(['name' => 'Licht']);
        [$light] = $this->userWithContract();
        $light->assignedCrafts()->attach($otherCraft->id);
        User::factory()->create()->assignedCrafts()->attach($this->craft->id); // ohne Konto

        $this->actingAsToken($person);
        $this->getJson('/api/v1/ai/work-time/overview?month=2026-11')
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_work_time_access']);

        $this->actingAsToken($this->grant(User::factory()->create(), PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value));
        $response = $this->getJson("/api/v1/ai/work-time/overview?month=2026-11&craft_id={$this->craft->id}&sort=difference")
            ->assertOk();

        $this->assertSame('2026-11', $response->json('month'));
        $this->assertSame(['id' => $this->craft->id, 'name' => 'Ton'], $response->json('craft'));
        $this->assertSame('difference', $response->json('sort'));
        $this->assertSame([$person->id], array_column(array_column($response->json('data'), 'person'), 'id'));
        $this->assertIsInt($response->json('data.0.actual_minutes'));
        $this->assertIsInt($response->json('data.0.balance_minutes'));

        $this->getJson('/api/v1/ai/work-time/overview?sort=name')->assertUnprocessable();
        $this->getJson('/api/v1/ai/work-time/overview?craft_id=999999999')->assertNotFound();
    }

    // --- S17 Raumauslastung --------------------------------------------------------------------

    #[Test]
    public function room_utilization_counts_overlaps_once_within_the_day_window(): void
    {
        $probe = EventType::factory()->create(['name' => 'Probe']);
        $show = EventType::factory()->create(['name' => 'Vorstellung']);
        $this->event(self::DAY . ' 10:00:00', self::DAY . ' 12:00:00', ['event_type_id' => $probe->id]);
        $this->event(self::DAY . ' 11:00:00', self::DAY . ' 13:00:00', ['event_type_id' => $probe->id]);
        $this->event(self::DAY . ' 07:00:00', self::DAY . ' 09:00:00', ['event_type_id' => $show->id]);
        $this->event(self::DAY . ' 14:00:00', self::DAY . ' 18:00:00', ['is_planning' => true]);
        $empty = Room::factory()->create(['name' => 'Leer']);
        $this->actingAsToken(User::factory()->create());

        $response = $this->getJson('/api/v1/ai/rooms/utilization?from=' . self::DAY . '&to=' . self::DAY)->assertOk();

        $this->assertSame(['start' => '08:00', 'end' => '24:00', 'source' => 'default'], $response->json('window'));
        $row = collect($response->json('data'))->firstWhere('room.id', $this->room->id);
        $this->assertSame(240, $row['booked_minutes']); // 08–09 + 10–13
        $this->assertSame(960, $row['available_minutes']);
        $this->assertEqualsWithDelta(25.0, $row['utilization_percent'], 0.01);
        $this->assertSame(3, $row['events']);
        $this->assertSame(1, $row['days_booked']);
        $this->assertSame([['name' => 'Probe', 'minutes' => 180], ['name' => 'Vorstellung', 'minutes' => 60]], $row['top_event_types']);
        $this->assertSame($this->room->id, $response->json('data.0.room.id'));

        $single = $this->getJson('/api/v1/ai/rooms/utilization?from=' . self::DAY . '&to=2026-11-04&room_id=' . $empty->id)
            ->assertOk();
        $this->assertCount(1, $single->json('data'));
        $this->assertSame(0, $single->json('data.0.booked_minutes'));
        $this->assertSame(1920, $single->json('data.0.available_minutes'));

        $this->getJson('/api/v1/ai/rooms/utilization?from=2026-11-01&to=2027-03-01')->assertUnprocessable();
        $this->getJson('/api/v1/ai/rooms/utilization?from=' . self::DAY . '&to=' . self::DAY . '&room_id=999999999')
            ->assertNotFound();
    }

    // --- S18 Projektfilter -------------------------------------------------------------------

    #[Test]
    public function projects_can_be_filtered_by_budget_deadline_state_done_and_manager(): void
    {
        $user = User::factory()->create();
        $manager = User::factory()->create();
        $state = ProjectState::factory()->create(['name' => 'In Planung']);
        $due = Project::factory()->create([
            'name' => 'Faellig', 'budget_deadline' => '2026-11-15', 'state' => $state->id, 'marked_as_done' => false,
        ]);
        $later = Project::factory()->create(['name' => 'Spaeter', 'budget_deadline' => '2027-01-15', 'marked_as_done' => true]);
        foreach ([$due, $later] as $project) {
            $project->users()->attach($user);
        }
        $due->users()->attach($manager, ['is_manager' => true]);
        $this->actingAsToken($user);

        $byDeadline = $this->getJson('/api/v1/ai/projects?budget_deadline_from=2026-11-01&budget_deadline_to=2026-11-30')
            ->assertOk();
        $this->assertSame([$due->id], array_column($byDeadline->json('data'), 'id'));
        $this->assertSame('2026-11-15', $byDeadline->json('data.0.budget_deadline'));
        $this->assertSame($state->id, $byDeadline->json('data.0.state_id'));
        $this->assertSame('In Planung', $byDeadline->json('data.0.state'));
        $this->assertFalse($byDeadline->json('data.0.marked_as_done'));

        $this->assertSame([$due->id], array_column(
            $this->getJson("/api/v1/ai/projects?state_id={$state->id}")->assertOk()->json('data'),
            'id'
        ));
        $this->assertSame([$later->id], array_column(
            $this->getJson('/api/v1/ai/projects?marked_as_done=1')->assertOk()->json('data'),
            'id'
        ));
        $this->assertSame([$due->id], array_column(
            $this->getJson("/api/v1/ai/projects?manager_id={$manager->id}")->assertOk()->json('data'),
            'id'
        ));
        $this->getJson('/api/v1/ai/projects?budget_deadline_from=2026-11-30&budget_deadline_to=2026-11-01')
            ->assertUnprocessable();

        $states = $this->getJson('/api/v1/ai/project-states?q=Planung')->assertOk();
        $this->assertContains(['id' => $state->id, 'name' => 'In Planung'], $states->json('data'));
    }

    // --- Lookups ---------------------------------------------------------------------------------

    #[Test]
    public function checklists_and_qualifications_can_be_looked_up(): void
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user);
        $own = Checklist::factory()->create([
            'user_id' => $user->id, 'project_id' => null, 'name' => 'KI4 Meine', 'private' => true,
        ]);
        $inProject = Checklist::factory()->create([
            'user_id' => null, 'project_id' => $this->project->id, 'name' => 'KI4 Technik', 'private' => false,
        ]);
        Checklist::factory()->create([
            'user_id' => User::factory()->create()->id, 'project_id' => null, 'name' => 'KI4 Fremd', 'private' => true,
        ]);
        $this->actingAsToken($user);

        $all = $this->getJson('/api/v1/ai/checklists?q=KI4')->assertOk();
        $this->assertSame([$own->id, $inProject->id], array_column($all->json('data'), 'id'));
        $this->assertSame([
            'id' => $inProject->id, 'name' => 'KI4 Technik',
            'project' => ['id' => $this->project->id, 'name' => 'Tosca'], 'private' => false,
        ], $all->json('data.1'));
        $this->assertTrue($all->json('data.0.private'));

        $this->assertSame([$inProject->id], array_column(
            $this->getJson("/api/v1/ai/checklists?project_id={$this->project->id}")->assertOk()->json('data'),
            'id'
        ));

        $qualifications = $this->getJson('/api/v1/ai/qualifications?q=Tontech')->assertOk();
        $this->assertSame([['id' => $this->qualification->id, 'name' => 'Tontechnik']], $qualifications->json('data'));
        $this->assertSame(1, $qualifications->json('total'));
    }
}
