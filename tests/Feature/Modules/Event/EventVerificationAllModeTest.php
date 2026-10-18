<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Prüfmodus „alle“: erst wenn alle Prüfenden zugestimmt haben, wird der Termin fest. Vorher zählten
 * offene Prüfungen nicht mit – bei drei Prüfenden reichte die erste Zustimmung, und die Ersteller*in
 * bekam „vollständig freigegeben“.
 */
final class EventVerificationAllModeTest extends FeatureTestCase
{
    #[Test]
    public function the_event_is_confirmed_only_after_every_verifier_approved(): void
    {
        $requester = $this->actingAsAdmin();
        $type = EventType::factory()->create(['verification_mode' => 'all']);
        $event = Event::factory()->create(['event_type_id' => $type->id, 'is_planning' => true]);
        $uuid = (string) Str::uuid();
        $verifications = User::factory()->count(3)->create()->map(fn (User $verifier) => EventVerification::create([
            'uuid' => $uuid,
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => 'pending',
            'request_user_id' => $requester->id,
        ]));
        $service = app(EventVerificationService::class);

        $service->approveVerification($verifications[0]);
        $service->approveVerification($verifications[1]->fresh());
        $this->assertTrue((bool) $event->fresh()->is_planning);

        $service->approveVerification($verifications[2]->fresh());
        $this->assertFalse((bool) $event->fresh()->is_planning);
    }
}
