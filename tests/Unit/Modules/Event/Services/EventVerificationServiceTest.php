<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventVerificationServiceTest extends TestCase
{
    private EventVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        EventFacade::fake();
        $this->mock(NotificationService::class, function ($mock): void {
            $mock->shouldReceive('setIcon')->andReturnSelf();
            $mock->shouldReceive('setPriority')->andReturnSelf();
            $mock->shouldReceive('setNotificationConstEnum')->andReturnSelf();
            $mock->shouldReceive('setTitle')->andReturnSelf();
            $mock->shouldReceive('setBroadcastMessage')->andReturnSelf();
            $mock->shouldReceive('setDescription')->andReturnSelf();
            $mock->shouldReceive('setNotificationTo')->andReturnSelf();
            $mock->shouldReceive('createNotification')->andReturnNull();
        });
        $this->service = app(EventVerificationService::class);
    }

    #[Test]
    public function get_planned_events_returns_events_for_user_without_verifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $eventType = EventType::factory()->create();

        $planned = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => true,
            'event_type_id' => $eventType->id,
        ]);
        // Other user's event
        Event::factory()->create([
            'user_id' => $other->id,
            'is_planning' => true,
        ]);

        $events = $this->service->getPlannedEvents($user);

        $this->assertInstanceOf(EloquentCollection::class, $events);
        $this->assertTrue($events->contains('id', $planned->id));
        $this->assertCount(1, $events);
    }

    #[Test]
    public function get_counts_by_user_returns_zero_counts_when_no_data(): void
    {
        $user = User::factory()->create();

        $counts = $this->service->getCountsByUser($user);

        $this->assertSame(0, $counts['requests']);
        $this->assertSame(0, $counts['approved']);
        $this->assertSame(0, $counts['pending']);
        $this->assertSame(0, $counts['rejected']);
    }

    #[Test]
    public function get_counts_by_user_aggregates_verifications_by_status(): void
    {
        $user = User::factory()->create();
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => true,
            'event_type_id' => $eventType->id,
        ]);

        EventVerification::create([
            'uuid' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'verifier_id' => $user->id,
            'verifier_type' => User::class,
            'status' => 'pending',
            'request_user_id' => $user->id,
        ]);
        EventVerification::create([
            'uuid' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'verifier_id' => $user->id,
            'verifier_type' => User::class,
            'status' => 'approved',
            'request_user_id' => $user->id,
        ]);

        $counts = $this->service->getCountsByUser($user);

        $this->assertSame(1, $counts['requests']);
        $this->assertSame(1, $counts['approved']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(0, $counts['rejected']);
    }

    #[Test]
    public function cancel_verification_sets_event_to_planning_and_removes_verifications(): void
    {
        $user = User::factory()->create();
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => false,
            'event_type_id' => $eventType->id,
        ]);
        EventVerification::create([
            'uuid' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'verifier_id' => $user->id,
            'verifier_type' => User::class,
            'status' => 'pending',
            'request_user_id' => $user->id,
        ]);

        $this->service->cancelVerification($event->fresh());

        $this->assertTrue((bool) $event->fresh()->is_planning);
        $this->assertSame(0, EventVerification::where('event_id', $event->id)->count());
    }

    #[Test]
    public function request_verification_with_none_mode_sets_event_not_planning(): void
    {
        $user = User::factory()->create();
        $eventType = EventType::factory()->create(['verification_mode' => 'none']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => true,
            'occupancy_option' => true,
            'event_type_id' => $eventType->id,
        ]);

        $this->service->requestVerification($event->fresh(), $user);

        $fresh = $event->fresh();
        $this->assertFalse((bool) $fresh->is_planning);
        $this->assertFalse((bool) $fresh->occupancy_option);
    }

    #[Test]
    public function reject_verification_marks_status_and_re_enables_planning(): void
    {
        $user = User::factory()->create();
        $verifier = User::factory()->create();
        $eventType = EventType::factory()->create(['verification_mode' => 'all']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => false,
            'event_type_id' => $eventType->id,
        ]);
        $verification = EventVerification::create([
            'uuid' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => 'pending',
            'request_user_id' => $user->id,
        ]);

        $this->service->rejectVerification($verification->fresh(), 'because');

        $this->assertSame('rejected', $verification->fresh()->status);
        $this->assertSame('because', $verification->fresh()->rejection_reason);
        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function reject_verification_by_event_returns_when_no_pending(): void
    {
        $user = User::factory()->create();
        $verifier = User::factory()->create();
        $eventType = EventType::factory()->create(['verification_mode' => 'all']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'is_planning' => true,
            'event_type_id' => $eventType->id,
        ]);

        // No pending verification - method should just return without error
        $this->service->rejectVerificationByEvent($event->fresh(), $verifier, 'reason');

        $this->assertSame(0, EventVerification::where('event_id', $event->id)->count());
    }
}
