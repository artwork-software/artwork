<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-aggregates: GET /ai/people/{type}/{id}/shifts — Schichten einer Person im Zeitraum (Entgrenzen P1).
 */
final class AiPersonShiftsApiTest extends TestCase
{
    use GrantsAiAccess;

    private Craft $craft;

    private ShiftQualification $qualification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableAiAssistant();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $this->craft = Craft::factory()->create(['name' => 'Ton']);
        $this->qualification = ShiftQualification::factory()->create(['name' => 'Meister']);
    }

    private function actingAsToken(User $user): User
    {
        $this->app['auth']->forgetGuards();
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);

        return $user;
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web'));

        return $user->fresh();
    }

    /** @param array<string, mixed> $attributes */
    private function shift(array $attributes = []): Shift
    {
        return ShiftChangeRecorder::withoutRecording(fn (): Shift => Shift::factory()->create([
            'craft_id' => $this->craft->id,
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-16',
            'start' => '10:00',
            'end' => '18:00',
            'break_minutes' => 30,
            'is_committed' => true,
            ...$attributes,
        ]));
    }

    private function assign(Shift $shift, User|Freelancer $person): void
    {
        $relation = $person instanceof User ? $shift->users() : $shift->freelancer();
        $relation->attach($person->id, [
            'shift_qualification_id' => $this->qualification->id,
            'is_overbooked' => false,
            'shift_count' => 1,
        ]);
    }

    #[Test]
    public function person_shifts_returns_shifts_with_minutes_project_room(): void
    {
        $person = User::factory()->create(['first_name' => 'Rhino', 'last_name' => 'Muster']);
        $room = Room::factory()->create(['name' => 'Saal']);
        $project = Project::factory()->create(['name' => 'Faust']);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => '2026-09-16 10:00:00',
            'end_time' => '2026-09-16 18:00:00',
            'eventName' => 'Probe',
        ]);
        $this->assign($this->shift(['event_id' => $event->id, 'project_id' => $project->id]), $person);
        // Ueber Mitternacht, ohne Termin
        $this->assign($this->shift([
            'start_date' => '2026-09-20', 'end_date' => '2026-09-21', 'start' => '22:00', 'end' => '02:00',
            'break_minutes' => 0, 'event_id' => null, 'project_id' => null,
        ]), $person);
        // Ausserhalb des Zeitraums und fremde Schicht
        $this->assign($this->shift(['start_date' => '2026-10-20', 'end_date' => '2026-10-20']), $person);
        $this->assign($this->shift(), User::factory()->create());
        $this->actingAsToken($this->viewer());

        $payload = $this->getJson("/api/v1/ai/people/user/{$person->id}/shifts?from=2026-09-01&to=2026-09-30")
            ->assertOk()
            ->json();

        $this->assertSame(['type' => 'user', 'id' => $person->id, 'name' => 'Rhino Muster'], $payload['person']);
        $this->assertSame(2, $payload['total']);
        $this->assertSame(450 + 240, $payload['total_minutes']);
        $this->assertFalse($payload['truncated']);

        $first = $payload['shifts'][0];
        $this->assertSame(450, $first['minutes']);
        $this->assertSame(30, $first['break_minutes']);
        $this->assertSame('Ton', $first['craft']['name']);
        $this->assertSame('Meister', $first['qualification']['name']);
        $this->assertSame('Probe', $first['event']['name']);
        $this->assertSame('Faust', $first['project']['name']);
        $this->assertSame('Saal', $first['room']['name']);
        $this->assertStringStartsWith('2026-09-16T10:00:00', $first['start']);

        $second = $payload['shifts'][1];
        $this->assertSame(240, $second['minutes']);
        $this->assertNull($second['event']);
        $this->assertStringStartsWith('2026-09-21T02:00:00', $second['end']);
    }

    #[Test]
    public function person_shifts_works_for_freelancers(): void
    {
        $freelancer = Freelancer::factory()->create();
        $this->assign($this->shift(), $freelancer);
        $this->actingAsToken($this->viewer());

        $payload = $this->getJson("/api/v1/ai/people/freelancer/{$freelancer->id}/shifts?from=2026-09-01&to=2026-09-30")
            ->assertOk()
            ->json();

        $this->assertSame('freelancer', $payload['person']['type']);
        $this->assertSame(1, $payload['total']);
    }

    #[Test]
    public function person_shifts_own_without_shift_plan_access(): void
    {
        $me = User::factory()->create();
        $this->assign($this->shift(), $me);
        $this->actingAsToken($me);

        $this->getJson("/api/v1/ai/people/user/{$me->id}/shifts?from=2026-09-01&to=2026-09-30")
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    #[Test]
    public function person_shifts_forbidden_without_shift_plan_access(): void
    {
        $other = User::factory()->create();
        $this->actingAsToken(User::factory()->create());

        $this->getJson("/api/v1/ai/people/user/{$other->id}/shifts?from=2026-09-01&to=2026-09-30")
            ->assertForbidden()
            ->assertExactJson(['error' => 'no_shift_plan_access']);
    }

    #[Test]
    public function person_shifts_range_366_days_ok_367_rejected(): void
    {
        $me = $this->actingAsToken(User::factory()->create());

        $this->getJson("/api/v1/ai/people/user/{$me->id}/shifts?from=2026-01-01&to=2027-01-02")->assertOk();
        $this->getJson("/api/v1/ai/people/user/{$me->id}/shifts?from=2026-01-01&to=2027-01-03")->assertStatus(422);
    }

    #[Test]
    public function person_shifts_unknown_person_404(): void
    {
        $this->actingAsToken($this->viewer());

        $this->getJson('/api/v1/ai/people/user/999999/shifts?from=2026-09-01&to=2026-09-30')->assertNotFound();
        $this->getJson('/api/v1/ai/people/robot/1/shifts?from=2026-09-01&to=2026-09-30')->assertNotFound();
    }
}
