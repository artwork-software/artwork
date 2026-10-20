<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Core\Casts\TimeAgoCast;
use Artwork\Modules\Calendar\DTO\BroadcastEventDTOWithVerifications;
use Artwork\Modules\Calendar\DTO\EventDTOWithVerifications;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Feature\FeatureTestCase;

/**
 * EventVerification::created_at ist per TranslatedDateTimeCast ein übersetzter String. Die DTOs riefen
 * darauf toIso8601String() auf → „Call to a member function toIso8601String() on string“ beim Broadcast
 * eines Planungstermins mit abgelehnter Prüfung.
 */
final class EventVerificationsSerializationTest extends FeatureTestCase
{
    #[Test]
    public function broadcast_dto_serializes_rejected_verifications_with_iso_timestamp(): void
    {
        $verification = $this->createRejectedVerification();

        $dto = BroadcastEventDTOWithVerifications::formModel($verification->event);

        $this->assertCount(1, $dto->verifications);
        $this->assertSame($verification->id, $dto->verifications[0]['id']);
        $this->assertSame(Carbon::parse('2026-10-14 09:30:00')->toIso8601String(), $dto->verifications[0]['created_at']);
    }

    #[Test]
    public function event_dto_serializes_preloaded_verifications_with_iso_timestamp(): void
    {
        $verification = $this->createRejectedVerification();
        $verificationsByEvent = EventVerification::query()->with('verifier')->get()->groupBy('event_id');

        $serializeVerifications = new ReflectionMethod(EventDTOWithVerifications::class, 'serializeVerifications');

        $serialized = $serializeVerifications->invoke(null, $verificationsByEvent, $verification->event_id);

        $this->assertSame(Carbon::parse('2026-10-14 09:30:00')->toIso8601String(), $serialized[0]['created_at']);
    }

    #[Test]
    public function created_at_iso8601_returns_null_without_timestamp(): void
    {
        $this->assertNull((new EventVerification())->createdAtIso8601());
    }

    /**
     * toArray() reicht created_at vor dem Cast als UTC-ISO-String durch – die Eingangsliste der Prüfenden
     * (EventVerificationController::index) zeigte dadurch die UTC-Uhrzeit (07:30 statt 09:30).
     */
    #[Test]
    public function serialized_translated_timestamp_shows_local_time(): void
    {
        $verification = $this->createRejectedVerification();

        $this->assertSame($verification->created_at, $verification->toArray()['created_at']);
        $this->assertStringContainsString('09:30', $verification->toArray()['created_at']);
    }

    #[Test]
    public function time_ago_cast_keeps_missing_timestamps_empty(): void
    {
        $cast = new TimeAgoCast();

        $this->assertNull($cast->get(new EventVerification(), 'created_at', null, []));
    }

    private function createRejectedVerification(): EventVerification
    {
        $requester = $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => true]);
        $verifier = User::factory()->create();

        Carbon::setTestNow('2026-10-14 09:30:00');
        $verification = EventVerification::create([
            'uuid' => (string) Str::uuid(),
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => 'rejected',
            'request_user_id' => $requester->id,
        ]);
        Carbon::setTestNow();

        return $verification->fresh();
    }
}
