<?php

namespace Tests\Feature;

use App\Events\UserStatusUpdated;
use App\Events\UserWentOffline;
use Artwork\Modules\Event\Events\BulkEventChanged;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Events\RemoveEvent;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\IndividualTimes\Events\IndividualTimeChanged;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Events\WorkerAvailabilityChanged;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kanal-Autorisierung in routes/channels.php und schlanke Payloads ohne Gehalts-/Kontaktdaten.
 */
final class SecurityAuditBroadcastRegressionTest extends TestCase
{
    private const SOCKET_ID = '1234.5678';

    protected function setUp(): void
    {
        parent::setUp();

        // Der log-Broadcaster prüft in auth() nichts; die beim Boot registrierten Channel-Callbacks
        // werden auf den Pusher-Broadcaster übernommen.
        $registered = Broadcast::driver();

        config([
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => '1',
                'options' => ['cluster' => 'eu', 'useTLS' => false],
            ],
        ]);

        $pusher = Broadcast::driver('pusher');
        foreach ($registered->getChannels() as $channel => $callback) {
            $pusher->channel($channel, $callback);
        }

        config(['broadcasting.default' => 'pusher']);
    }

    private function authorizeChannel(string $channelName): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/broadcasting/auth', [
            'channel_name' => $channelName,
            'socket_id' => self::SOCKET_ID,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function teamPivot(): array
    {
        return ['access_budget' => false, 'is_manager' => false, 'can_write' => false, 'delete_permission' => false];
    }

    // ---------------------------------------------------------------- project.{id}

    #[Test]
    public function project_channel_denies_user_without_project_access(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([]);

        $this->authorizeChannel("private-project.{$project->id}")->assertForbidden();
    }

    #[Test]
    public function project_channel_denies_unknown_project(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->authorizeChannel('private-project.999999999')->assertForbidden();
    }

    #[Test]
    public function project_channel_allows_team_member(): void
    {
        $project = Project::factory()->create();
        $member = $this->actingAsUserWith([]);
        $project->users()->attach([$member->id => $this->teamPivot()]);

        $this->authorizeChannel("private-project.{$project->id}")->assertOk();
    }

    #[Test]
    public function project_channel_allows_user_with_global_view_permission_and_admin(): void
    {
        $project = Project::factory()->create();

        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $this->authorizeChannel("private-project.{$project->id}")->assertOk();

        $this->actingAsAdmin();
        $this->authorizeChannel("private-project.{$project->id}")->assertOk();
    }

    #[Test]
    public function project_channel_denies_guest(): void
    {
        $project = Project::factory()->create();

        $this->authorizeChannel("private-project.{$project->id}")->assertForbidden();
    }

    // ---------------------------------------------------------------- Schichtplan-Kanäle

    #[Test]
    public function shift_plan_channels_deny_user_without_shift_plan_permission(): void
    {
        $room = Room::factory()->create();
        $shift = Shift::factory()->create();
        $this->actingAsUserWith([]);

        $this->authorizeChannel('private-shift-plan.multi-shifts')->assertForbidden();
        $this->authorizeChannel('private-shifts')->assertForbidden();
        $this->authorizeChannel("private-shift-plan.room.{$room->id}")->assertForbidden();
        $this->authorizeChannel("private-destroy.events.room.{$room->id}")->assertForbidden();
        $this->authorizeChannel("private-shift-plan.shift.{$shift->id}")->assertForbidden();
    }

    #[Test]
    public function shift_plan_channels_allow_user_with_view_shift_plan_permission(): void
    {
        $room = Room::factory()->create();
        $shift = Shift::factory()->create();
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->authorizeChannel('private-shift-plan.multi-shifts')->assertOk();
        $this->authorizeChannel('private-shifts')->assertOk();
        $this->authorizeChannel("private-shift-plan.room.{$room->id}")->assertOk();
        $this->authorizeChannel("private-destroy.events.room.{$room->id}")->assertOk();
        $this->authorizeChannel("private-shift-plan.shift.{$shift->id}")->assertOk();
    }

    #[Test]
    public function shift_plan_channels_allow_shift_planner_and_admin(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->authorizeChannel('private-shift-plan.multi-shifts')->assertOk();

        $this->actingAsAdmin();
        $this->authorizeChannel('private-shift-plan.multi-shifts')->assertOk();
    }

    // ---------------------------------------------------------------- Auth::check()-Kanäle

    #[Test]
    public function authenticated_only_channels_allow_any_user_and_deny_guests(): void
    {
        $room = Room::factory()->create();

        foreach (['private-bulk.events', 'private-users.status', "private-event.room.{$room->id}"] as $channel) {
            $this->authorizeChannel($channel)->assertForbidden();
        }

        $this->actingAsUserWith([]);

        foreach (['private-bulk.events', 'private-users.status', "private-event.room.{$room->id}"] as $channel) {
            $this->authorizeChannel($channel)->assertOk();
        }

        $this->authorizeChannel('private-event.room.999999999')->assertForbidden();
    }

    // ---------------------------------------------------------------- Planungstermine (14.10.2026)

    #[Test]
    public function planning_channels_require_the_planning_calendar_permission(): void
    {
        $room = Room::factory()->create();
        $planningChannels = ['private-bulk.events.planning', "private-event.planning.room.{$room->id}"];

        $this->actingAsUserWith([]);
        foreach ($planningChannels as $channel) {
            $this->authorizeChannel($channel)->assertForbidden();
        }

        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        foreach ($planningChannels as $channel) {
            $this->authorizeChannel($channel)->assertOk();
        }
        $this->authorizeChannel('private-event.planning.room.999999999')->assertForbidden();
    }

    #[Test]
    public function planned_events_reach_the_open_channels_only_without_content(): void
    {
        $room = Room::factory()->create();
        $planned = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => true,
            'eventName' => 'GeheimePlanung',
            'name' => 'GeheimePlanung',
        ]);

        foreach ([
            new EventCreated($planned, $room->id),
            new EventUpdated($planned, $room->id),
            new BulkEventChanged($planned, 'updated'),
        ] as $broadcast) {
            $this->assertStringNotContainsString('planning', $broadcast->broadcastOn()->name);
            $payload = json_encode($broadcast->broadcastWith());
            $this->assertStringNotContainsString('GeheimePlanung', $payload, $broadcast::class);
            $this->assertTrue($broadcast->broadcastWith()['event']['redacted'], $broadcast::class);

            $companion = $broadcast->planningCompanion();
            $this->assertNotNull($companion, $broadcast::class);
            $this->assertStringContainsString('planning', $companion->broadcastOn()->name);
            $this->assertStringContainsString('GeheimePlanung', json_encode($companion->broadcastWith()));
        }

        $bulkPayload = json_decode(json_encode(BulkEventChanged::eventPayload($planned->fresh())), true);
        $this->assertSame(['id', 'name', 'position'], array_keys($bulkPayload['room']));

        $removal = json_encode((new RemoveEvent($planned, $room->id))->broadcastWith());
        $this->assertStringNotContainsString('GeheimePlanung', $removal);
    }

    #[Test]
    public function fixed_events_have_no_planning_companion(): void
    {
        $fixed = Event::factory()->create(['is_planning' => false]);

        $this->assertNull((new EventCreated($fixed, $fixed->room_id))->planningCompanion());
        $payload = json_decode(json_encode((new EventCreated($fixed, $fixed->room_id))->broadcastWith()), true);
        $this->assertArrayNotHasKey('redacted', $payload['event']);
        $this->assertSame($fixed->id, $payload['event']['id']);
    }

    #[Test]
    public function safe_broadcast_sends_the_planning_companion_too(): void
    {
        Bus::fake();
        $planned = Event::factory()->create(['is_planning' => true]);

        SafeBroadcast::send(new EventUpdated($planned, $planned->room_id));

        $channels = Bus::dispatched(BroadcastEvent::class)
            ->map(fn (BroadcastEvent $job) => $job->event->broadcastOn()->name)
            ->all();
        $this->assertSame([
            "private-event.room.{$planned->room_id}",
            "private-event.planning.room.{$planned->room_id}",
        ], $channels);
    }

    // ---------------------------------------------------------------- Payload / Kanaltyp

    #[Test]
    public function shift_assigned_payload_contains_no_salary_or_contact_data(): void
    {
        $shift = Shift::factory()->create();
        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        $payload = json_decode(
            json_encode((new ShiftAssigned($user, Shift::query()->findOrFail($shift->id)))->broadcastWith()),
            true
        );

        $forbidden = ['salary_per_hour', 'salary_description', 'weekly_working_hours', 'email', 'phone_number', 'password'];
        $this->assertSame([], array_intersect($forbidden, $this->collectKeys($payload)));

        $this->assertSame($shift->id, $payload['shift']['id']);
        $this->assertSame($user->id, $payload['user']['id']);
        $this->assertSame($user->id, $payload['shift']['workers'][0]['id']);
    }

    #[Test]
    public function formerly_public_events_broadcast_on_private_channels(): void
    {
        $event = Event::factory()->create();

        $channels = [
            ...(new MultiShiftCreateInShiftPlan(collect()))->broadcastOn(),
            (new BulkEventChanged($event, 'updated'))->broadcastOn(),
            (new UserStatusUpdated(1, 'online'))->broadcastOn(),
            (new UserWentOffline(1))->broadcastOn(),
            ...(new IndividualTimeChanged(1, 0))->broadcastOn(),
            ...(new WorkerAvailabilityChanged(1, 0))->broadcastOn(),
        ];

        $this->assertCount(6, $channels);
        foreach ($channels as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel, "Kanal {$channel} muss privat sein");
            $this->assertStringStartsWith('private-', (string) $channel);
        }
    }

    /**
     * @param array<mixed> $data
     * @return array<int, string>
     */
    private function collectKeys(array $data): array
    {
        $keys = [];
        foreach ($data as $key => $value) {
            $keys[] = (string) $key;
            if (is_array($value)) {
                $keys = [...$keys, ...$this->collectKeys($value)];
            }
        }

        return array_values(array_unique($keys));
    }
}
