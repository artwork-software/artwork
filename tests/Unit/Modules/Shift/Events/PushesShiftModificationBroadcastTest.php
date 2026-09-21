<?php

namespace Tests\Unit\Modules\Shift\Events;

use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Broadcast-Payload der Schicht-Events. ShiftUpdated/PushesShiftModification (ungenutzt,
 * volles users-Relation-Array im Payload) wurden im Sicherheits-Audit 21.09.2026 entfernt.
 */
final class PushesShiftModificationBroadcastTest extends TestCase
{
    #[Test]
    public function shift_assigned_broadcast_uses_slim_shift_dto_payload(): void
    {
        $shift = Shift::factory()->create();
        $assignedUser = User::factory()->create();
        $shift->users()->attach($assignedUser->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        $payload = (new ShiftAssigned($assignedUser, Shift::query()->findOrFail($shift->id)))
            ->broadcastWith();

        // Sicherheits-Audit 21.09.2026 (G): kein shift->toArray()/user->toArray() mehr,
        // sondern ShiftDTO + schlanker User-Auszug (keine Gehalts-/Kontaktdaten)
        foreach (['shift', 'roomId', 'user', 'event'] as $key) {
            $this->assertArrayHasKey($key, $payload, "ShiftAssigned-Payload muss '{$key}' enthalten");
        }
        $this->assertSame($shift->id, $payload['shift']->id);
        $this->assertCount(1, $payload['shift']->workers);
        $this->assertSame($assignedUser->id, $payload['shift']->workers[0]['id']);
        $this->assertSame($assignedUser->id, $payload['user']['id']);
        $this->assertArrayNotHasKey('email', $payload['user']);
    }
}
