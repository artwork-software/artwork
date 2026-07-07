<?php

namespace Tests\Unit\Modules\Shift\Events;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Events\ShiftUpdated;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression nach Entfernung des globalen Shift-$with: der Broadcast-Payload
 * (PushesShiftModification::broadcastWith) muss die Worker-Relationen weiterhin
 * enthalten (loadMissing) und Schichten ohne Event dürfen nicht crashen.
 */
final class PushesShiftModificationBroadcastTest extends TestCase
{
    #[Test]
    public function broadcast_payload_contains_worker_relations_and_event(): void
    {
        $craft = Craft::factory()->create();
        $event = Event::factory()->create();
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
        ]);
        $worker = User::factory()->create();
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        // Frisch laden, damit garantiert KEINE Relationen vorgeladen sind —
        // broadcastWith() muss sie selbst nachladen
        $freshShift = Shift::query()->findOrFail($shift->id);

        $payload = (new ShiftUpdated($freshShift))->broadcastWith();

        foreach (['craft', 'users', 'freelancer', 'service_provider', 'committed_by', 'event'] as $key) {
            $this->assertArrayHasKey($key, $payload, "Broadcast-Payload muss '{$key}' enthalten");
        }

        $this->assertSame($craft->id, $payload['craft']['id']);
        $this->assertCount(1, $payload['users']);
        $this->assertSame($worker->id, $payload['users'][0]['id']);
        $this->assertSame($event->id, $payload['event']['id']);
    }

    #[Test]
    public function broadcast_payload_handles_shift_without_event(): void
    {
        $shift = Shift::factory()->create(['event_id' => null]);

        $payload = (new ShiftUpdated(Shift::query()->findOrFail($shift->id)))->broadcastWith();

        $this->assertNull($payload['event']);
        $this->assertArrayHasKey('users', $payload);
    }

    #[Test]
    public function shift_assigned_broadcast_contains_worker_relations(): void
    {
        $shift = Shift::factory()->create();
        $assignedUser = User::factory()->create();
        $shift->users()->attach($assignedUser->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        $payload = (new ShiftAssigned($assignedUser, Shift::query()->findOrFail($shift->id)))
            ->broadcastWith();

        foreach (['craft', 'users', 'freelancer', 'service_provider', 'committed_by', 'user', 'event'] as $key) {
            $this->assertArrayHasKey($key, $payload, "ShiftAssigned-Payload muss '{$key}' enthalten");
        }
        $this->assertCount(1, $payload['users']);
    }
}
