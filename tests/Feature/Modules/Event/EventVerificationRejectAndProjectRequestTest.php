<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion von "Prüfung eines Termins ablehnen" und "Prüfung für alle geplanten Termine eines
 * Projekts anfragen" (Rechte deckt die Rollen-Matrix ab).
 */
final class EventVerificationRejectAndProjectRequestTest extends FeatureTestCase
{
    #[Test]
    public function verifier_rejects_own_pending_verification_with_reason(): void
    {
        $verifier = User::factory()->create();
        [$event] = $this->planningEventWithVerifiers('any', [$verifier]);
        $verification = $this->createVerification($event, $verifier);

        $this->actingAs($verifier)
            ->post(route('event-verifications.reject-by-event', $event), ['rejection_reason' => 'Raum belegt'])
            ->assertSuccessful();

        $fresh = $verification->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Raum belegt', $fresh->rejection_reason);
        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function admin_rejects_open_round_of_other_verifiers(): void
    {
        $admin = $this->actingAsAdmin();
        $verifier = User::factory()->create();
        [$event] = $this->planningEventWithVerifiers('any', [$verifier]);
        $uuid = (string) Str::uuid();
        $foreignVerification = $this->createVerification($event, $verifier, 'pending', $uuid);

        $this->post(route('event-verifications.reject-by-event', $event), ['rejection_reason' => 'Nein'])
            ->assertSuccessful();

        // Offene Prüfungen der Runde werden geschlossen, die Ablehnung steht auf der Admin-Person
        $this->assertNull($foreignVerification->fresh());
        $adminVerification = EventVerification::query()
            ->where('event_id', $event->id)
            ->where('verifier_id', $admin->id)
            ->first();
        $this->assertNotNull($adminVerification);
        $this->assertSame('rejected', $adminVerification->status);
        $this->assertSame($uuid, $adminVerification->uuid);
        $this->assertSame('Nein', $adminVerification->rejection_reason);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
    }

    #[Test]
    public function rejecting_without_own_verification_changes_nothing(): void
    {
        $verifier = User::factory()->create();
        $bystander = User::factory()->create();
        [$event] = $this->planningEventWithVerifiers('any', [$verifier]);
        $verification = $this->createVerification($event, $verifier);

        $this->actingAs($bystander)
            ->post(route('event-verifications.reject-by-event', $event), [])
            ->assertSuccessful();

        $this->assertSame('pending', $verification->fresh()->status);
    }

    #[Test]
    public function rejection_reason_must_be_text(): void
    {
        $verifier = User::factory()->create();
        [$event] = $this->planningEventWithVerifiers('any', [$verifier]);
        $verification = $this->createVerification($event, $verifier);

        $this->actingAs($verifier)
            ->postJson(route('event-verifications.reject-by-event', $event), ['rejection_reason' => ['x']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rejection_reason');

        $this->assertSame('pending', $verification->fresh()->status);
    }

    #[Test]
    public function long_multibyte_rejection_reason_is_rejected_instead_of_server_error(): void
    {
        $verifier = User::factory()->create();
        [$event] = $this->planningEventWithVerifiers('any', [$verifier]);
        $verification = $this->createVerification($event, $verifier);

        // TEXT fasst 65.535 Byte: 40.000 Umlaute (80.000 Byte) passten durch max:65000 und sprengten die Spalte
        $this->actingAs($verifier)
            ->postJson(route('event-verifications.reject-by-event', $event), [
                'rejection_reason' => str_repeat('ä', 40000),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rejection_reason');

        $this->assertSame('pending', $verification->fresh()->status);
    }

    #[Test]
    public function project_request_confirms_planning_events_without_verification_mode(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $type = EventType::factory()->create(['verification_mode' => 'none']);
        $planningEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $type->id,
            'is_planning' => true,
        ]);
        $fixedEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $type->id,
            'is_planning' => false,
        ]);
        $otherProjectEvent = Event::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'event_type_id' => $type->id,
            'is_planning' => true,
        ]);

        $this->post(route('projects.request-verification', $project))->assertSuccessful();

        $this->assertFalse((bool) $planningEvent->fresh()->is_planning);
        $this->assertFalse((bool) $fixedEvent->fresh()->is_planning);
        $this->assertTrue((bool) $otherProjectEvent->fresh()->is_planning);
    }

    #[Test]
    public function project_request_creates_rounds_but_skips_pending_and_verifierless_events(): void
    {
        $this->actingAsUserWith(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value);
        $project = Project::factory()->create();
        $verifier = User::factory()->create();
        [$openEvent] = $this->planningEventWithVerifiers('any', [$verifier], $project);
        [$alreadyPendingEvent] = $this->planningEventWithVerifiers('any', [$verifier], $project);
        $existing = $this->createVerification($alreadyPendingEvent, $verifier);
        [$verifierlessEvent] = $this->planningEventWithVerifiers('all', [], $project);

        $this->post(route('projects.request-verification', $project))->assertSuccessful();

        $this->assertSame(1, $openEvent->verifications()->where('status', 'pending')->count());
        $this->assertSame(
            [$existing->id],
            $alreadyPendingEvent->verifications()->pluck('id')->all()
        );
        $this->assertSame(0, $verifierlessEvent->verifications()->count());
        $this->assertTrue((bool) $openEvent->fresh()->is_planning);
    }

    #[Test]
    public function project_request_for_unknown_project_does_nothing(): void
    {
        $this->actingAsAdmin();

        $this->post(route('projects.request-verification', 99999999))->assertSuccessful();
    }

    /**
     * @param array<int, User> $verifiers
     * @return array{0: Event, 1: EventType}
     */
    private function planningEventWithVerifiers(string $mode, array $verifiers, ?Project $project = null): array
    {
        $type = EventType::factory()->create(['verification_mode' => $mode]);
        $type->verifiers()->sync(collect($verifiers)->pluck('id'));
        $event = Event::factory()->create([
            'event_type_id' => $type->id,
            'is_planning' => true,
            'project_id' => $project?->id ?? Project::factory()->create()->id,
        ]);

        return [$event, $type];
    }

    private function createVerification(
        Event $event,
        User $verifier,
        string $status = 'pending',
        ?string $uuid = null,
    ): EventVerification {
        return EventVerification::query()->create([
            'uuid' => $uuid ?? (string) Str::uuid(),
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => $status,
            'request_user_id' => $event->user_id,
        ]);
    }
}
