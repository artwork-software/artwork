<?php

namespace Tests\Feature\AiAssistant;

use App\Settings\ShiftSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationEnum;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Laravel\Passport\ClientRepository;
use Spatie\Permission\Models\Permission;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-api-schedule-shifts: GET /ai/me/schedule und GET /ai/shift-plan (Konzept B1 §3, §6, §8).
 */
final class AiScheduleShiftPlanApiTest extends TestCase
{
    /** Mittwoch der ISO-Woche 38/2026 (Montag: 14.09.2026). */
    private const DAY = '2026-09-16';

    private const WEEK = '2026-W38';

    private Craft $craft;

    private ShiftQualification $qualification;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $this->craft = Craft::factory()->create(['name' => 'Ton']);
        $this->qualification = ShiftQualification::factory()->create(['name' => 'Meister']);
    }

    /**
     * @param string[] $scopes
     *
     * `forgetGuards()` ist noetig, wenn ein Test zwei Tokens nacheinander benutzt: der
     * RequestGuard haelt den einmal aufgeloesten Benutzer fest, der zweite Bearer-Header wuerde
     * sonst ignoriert.
     */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();

        $token = $user->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    /** @param array<string, mixed> $attributes */
    private function makeShift(array $attributes = []): Shift
    {
        // Ohne Recorder: eine festgeschriebene Schicht erzeugte beim Anlegen sonst eine Aenderung.
        return ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create(array_merge([
            'craft_id' => $this->craft->id,
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => true,
        ], $attributes)));
    }

    private function assign(Shift $shift, User $user, bool $overbooked = false): void
    {
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $this->qualification->id,
            'is_overbooked' => $overbooked,
            'shift_count' => 1,
        ]);
    }

    /**
     * Recht mit dem Guard `web` vergeben — der Rechtekatalog existiert nur dort, und unter
     * `auth:api` waere die Guard-Ableitung mehrdeutig (HANDOVER_core §2).
     */
    private function grant(User $user, string $permission): User
    {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user->fresh();
    }

    private function userWithShiftPlanPermission(): User
    {
        return $this->grant(User::factory()->create(), PermissionEnum::VIEW_SHIFT_PLAN->value);
    }

    private function range(): string
    {
        return 'from=' . self::DAY . '&to=' . self::DAY;
    }

    // --- Scope, Zeitraum, Rechte ------------------------------------------------------------

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertForbidden();
        $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK)->assertForbidden();
    }

    #[Test]
    public function the_own_schedule_demands_a_period(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/me/schedule')->assertStatus(422);
    }

    #[Test]
    public function a_period_longer_than_the_maximum_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create());

        $to = CarbonImmutable::parse(self::DAY)->addDays((int) config('ai.max_range_days') + 1);

        $this->getJson('/api/v1/ai/me/schedule?from=' . self::DAY . '&to=' . $to->toDateString())
            ->assertStatus(422);
    }

    #[Test]
    public function the_shift_plan_demands_a_week(): void
    {
        $this->actingAsToken($this->userWithShiftPlanPermission());

        $this->getJson('/api/v1/ai/shift-plan')->assertStatus(422);
        $this->getJson('/api/v1/ai/shift-plan?week=2026-38')->assertStatus(422);
        $this->getJson('/api/v1/ai/shift-plan?week=2026-W99')->assertStatus(422);
    }

    #[Test]
    public function the_shift_plan_is_forbidden_without_the_view_permission(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK)->assertForbidden();
    }

    // --- me/schedule ------------------------------------------------------------------------

    #[Test]
    public function the_own_schedule_lists_shifts_availabilities_and_absences(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Tosca']);
        $event = Event::factory()->create(['project_id' => $project->id]);
        $shift = $this->makeShift(['event_id' => $event->id, 'project_id' => $project->id]);
        $this->assign($shift, $user);

        Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => $user->id,
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'comment' => 'Nur vormittags',
        ]);

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => self::DAY,
            'full_day' => true,
            'type' => VacationEnum::OFF_WORK->value,
        ]);

        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertOk()->json();

        $this->assertSame(1, $payload['shifts']['total']);
        $this->assertSame($shift->id, $payload['shifts']['data'][0]['id']);
        $this->assertSame('Ton', $payload['shifts']['data'][0]['craft']['name']);
        $this->assertSame('Meister', $payload['shifts']['data'][0]['qualification']);
        $this->assertSame('Tosca', $payload['shifts']['data'][0]['project']['name']);
        $this->assertSame($event->id, $payload['shifts']['data'][0]['event']['id']);
        $this->assertStringStartsWith(self::DAY . 'T10:00:00', $payload['shifts']['data'][0]['start']);

        $this->assertSame(1, $payload['availabilities']['total']);
        $this->assertSame('09:00', $payload['availabilities']['data'][0]['start_time']);
        $this->assertSame('Nur vormittags', $payload['availabilities']['data'][0]['comment']);

        $this->assertSame(1, $payload['absences']['total']);
        $this->assertTrue($payload['absences']['data'][0]['full_day']);
        $this->assertSame(VacationEnum::OFF_WORK->value, $payload['absences']['data'][0]['type']);
        $this->assertNull($payload['absences']['data'][0]['start_time']);
    }

    #[Test]
    public function the_own_schedule_uses_the_individual_times_of_the_assignment(): void
    {
        $user = User::factory()->create();
        $shift = $this->makeShift();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $this->qualification->id,
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'start_time' => '12:00',
            'end_time' => '15:00',
            'shift_count' => 1,
        ]);

        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertOk()->json();

        $this->assertStringStartsWith(self::DAY . 'T12:00:00', $payload['shifts']['data'][0]['start']);
        $this->assertStringStartsWith(self::DAY . 'T15:00:00', $payload['shifts']['data'][0]['end']);
    }

    #[Test]
    public function the_own_schedule_never_shows_the_shifts_of_someone_else(): void
    {
        $other = User::factory()->create();
        $shift = $this->makeShift();
        $this->assign($shift, $other);

        $user = User::factory()->create();
        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/me/schedule?' . $this->range() . '&user_id=' . $other->id)
            ->assertOk()
            ->json();

        $this->assertSame(0, $payload['shifts']['total']);
        $this->assertSame([], $payload['shifts']['data']);
    }

    #[Test]
    public function the_own_schedule_of_an_admin_stays_the_own_schedule(): void
    {
        $other = User::factory()->create();
        $shift = $this->makeShift();
        $this->assign($shift, $other);

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $other->id,
            'date' => self::DAY,
            'full_day' => true,
            'type' => VacationEnum::OFF_WORK->value,
        ]);

        $admin = $this->adminUser();
        $this->actingAsToken($admin->fresh());

        $payload = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertOk()->json();

        $this->assertSame(0, $payload['shifts']['total']);
        $this->assertSame(0, $payload['absences']['total']);
    }

    #[Test]
    public function uncommitted_shifts_follow_the_setting_of_the_own_roster(): void
    {
        ShiftSettings::fake(['hide_uncommitted_shifts_from_own_roster' => true]);

        $user = User::factory()->create();
        $shift = $this->makeShift(['is_committed' => false]);
        $this->assign($shift, $user);

        $this->actingAsToken($user);
        $hidden = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertOk()->json();

        $this->assertSame(0, $hidden['shifts']['total']);

        $allowed = $this->grant(User::factory()->create(), PermissionEnum::CAN_VIEW_OWN_UNCOMMITTED_SHIFTS->value);
        $this->assign($shift, $allowed);

        $this->actingAsToken($allowed);
        $visible = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())->assertOk()->json();

        $this->assertSame(1, $visible['shifts']['total']);
        $this->assertFalse($visible['shifts']['data'][0]['committed']);
    }

    #[Test]
    public function the_own_schedule_honours_the_list_limit(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $ignored) {
            $this->assign($this->makeShift(), $user);
        }

        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/me/schedule?' . $this->range() . '&limit=2')->assertOk()->json();

        $this->assertSame(3, $payload['shifts']['total']);
        $this->assertCount(2, $payload['shifts']['data']);
        $this->assertTrue($payload['shifts']['truncated']);
    }

    #[Test]
    public function free_text_is_truncated_to_the_configured_limit(): void
    {
        config(['ai.text_limit' => 20]);

        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => str_repeat('a', 100)]);
        $shift = $this->makeShift(['project_id' => $project->id]);
        $this->assign($shift, $user);

        $this->actingAsToken($user);

        $name = $this->getJson('/api/v1/ai/me/schedule?' . $this->range())
            ->assertOk()
            ->json('shifts.data.0.project.name');

        $this->assertLessThanOrEqual(21, mb_strlen((string) $name));
    }

    // --- shift-plan -------------------------------------------------------------------------

    #[Test]
    public function the_shift_plan_reports_demand_staffing_and_names(): void
    {
        $shift = $this->makeShift();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $this->qualification->id,
            'value' => 2,
        ]);
        $worker = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $this->assign($shift, $worker);

        $this->actingAsToken($this->userWithShiftPlanPermission());

        $payload = $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK)->assertOk()->json();

        $this->assertSame(self::WEEK, $payload['week']);
        $this->assertStringStartsWith('2026-09-14T00:00:00', $payload['period']['from']);
        $this->assertSame(1, $payload['total']);

        $entry = $payload['data'][0];
        $this->assertSame($shift->id, $entry['id']);
        $this->assertSame('Ton', $entry['craft']['name']);
        $this->assertSame(2, $entry['required']);
        $this->assertSame(1, $entry['staffed']);
        $this->assertSame(1, $entry['open']);
        $this->assertSame('Meister', $entry['qualifications'][0]['qualification']['name']);
        $this->assertSame('Ada Lovelace', $entry['assigned'][0]['display_name']);
        $this->assertSame('Meister', $entry['assigned'][0]['qualification']);
    }

    #[Test]
    public function an_overbooked_assignment_does_not_count_as_staffed(): void
    {
        $shift = $this->makeShift();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $this->qualification->id,
            'value' => 1,
        ]);
        $this->assign($shift, User::factory()->create(), true);

        $this->actingAsToken($this->userWithShiftPlanPermission());

        $entry = $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK)->assertOk()->json('data.0');

        $this->assertSame(0, $entry['staffed']);
        $this->assertSame(1, $entry['open']);
        $this->assertTrue($entry['assigned'][0]['overbooked']);
    }

    #[Test]
    public function the_shift_plan_filters_by_craft_and_week(): void
    {
        $wanted = $this->makeShift();
        $otherCraft = Craft::factory()->create(['name' => 'Licht']);
        $this->makeShift(['craft_id' => $otherCraft->id]);
        $this->makeShift(['start_date' => '2026-09-23', 'end_date' => '2026-09-23']);

        $this->actingAsToken($this->userWithShiftPlanPermission());

        $payload = $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK . '&craft_id=' . $this->craft->id)
            ->assertOk()
            ->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($wanted->id, $payload['data'][0]['id']);
        $this->assertSame('Ton', $payload['craft']['name']);
    }

    #[Test]
    public function the_shift_plan_honours_the_list_limit(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->makeShift();
        }

        $this->actingAsToken($this->userWithShiftPlanPermission());

        $payload = $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK . '&limit=2')->assertOk()->json();

        $this->assertSame(3, $payload['total']);
        $this->assertCount(2, $payload['data']);
        $this->assertTrue($payload['truncated']);
    }

    #[Test]
    public function an_admin_sees_the_shift_plan_like_the_interface_does(): void
    {
        $shift = $this->makeShift();
        $this->assign($shift, User::factory()->create());

        $this->actingAsToken($this->adminUser()->fresh());

        $this->getJson('/api/v1/ai/shift-plan?week=' . self::WEEK)
            ->assertOk()
            ->assertJsonPath('total', 1);
    }
}
