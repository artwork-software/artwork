<?php

namespace Tests\Feature;

use App\Events\UserStatusUpdated;
use App\Events\UserWentOffline;
use Artwork\Modules\Event\Events\BulkEventChanged;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\IndividualTimes\Events\IndividualTimeChanged;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Events\WorkerAvailabilityChanged;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
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
