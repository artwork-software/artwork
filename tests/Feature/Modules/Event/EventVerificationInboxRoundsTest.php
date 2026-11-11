<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kundenmeldung 10.11.2026: Eine Anfrage an drei Prüfende stand bei Admins dreimal in der Eingangsliste, und jedes
 * "Genehmigen" meldete der anfragenden Person den Namen der Person, an die die angeklickte Prüfung ging. Jetzt gibt
 * es je Anfrage eine Kachel, und genannt wird, wer tatsächlich entschieden hat.
 */
final class EventVerificationInboxRoundsTest extends FeatureTestCase
{
    #[Test]
    public function an_admin_sees_a_request_to_three_verifiers_only_once(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 3);
        $uuid = (string) Str::uuid();
        foreach ($verifiers as $verifier) {
            $this->createVerification($event, $verifier, 'pending', $uuid);
        }

        $this->get(route('event-verifications.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('verificationRequests.' . $event->event_type_id, 1)
                ->has('verificationRequests.' . $event->event_type_id . '.0.round_verifiers', 3)
                ->where(
                    'verificationRequests.' . $event->event_type_id . '.0.round_verifiers.0.status',
                    'pending'
                ));
    }

    #[Test]
    public function separate_rounds_and_events_stay_separate_entries(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 2);
        $otherEvent = Event::factory()->create(['event_type_id' => $event->event_type_id, 'is_planning' => true]);
        foreach ([$event, $otherEvent] as $plannedEvent) {
            $uuid = (string) Str::uuid();
            foreach ($verifiers as $verifier) {
                $this->createVerification($plannedEvent, $verifier, 'pending', $uuid);
            }
        }

        $this->get(route('event-verifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('verificationRequests.' . $event->event_type_id, 2));
    }

    #[Test]
    public function an_admin_who_is_a_verifier_sees_the_own_verification_as_the_entry(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $uuid = (string) Str::uuid();
        $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $own = $this->createVerification($event, $admin, 'pending', $uuid);
        $this->createVerification($event, $verifiers[1], 'approved', $uuid);

        $this->get(route('event-verifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('verificationRequests.' . $event->event_type_id, 1)
                ->where('verificationRequests.' . $event->event_type_id . '.0.id', $own->id)
                ->has('verificationRequests.' . $event->event_type_id . '.0.round_verifiers', 3));
    }

    #[Test]
    public function a_verifier_sees_only_the_own_open_verification_with_the_state_of_the_others(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 3);
        $uuid = (string) Str::uuid();
        $this->createVerification($event, $verifiers[0], 'approved', $uuid);
        $own = $this->createVerification($event, $verifiers[1], 'pending', $uuid);
        $this->createVerification($event, $verifiers[2], 'pending', $uuid);
        $this->actingAs($verifiers[1]);

        $this->get(route('event-verifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('verificationRequests.' . $event->event_type_id, 1)
                ->where('verificationRequests.' . $event->event_type_id . '.0.id', $own->id)
                ->where(
                    'verificationRequests.' . $event->event_type_id . '.0.round_verifiers.0.status',
                    'approved'
                ));
    }

    #[Test]
    public function an_admin_approving_a_foreign_entry_closes_the_request_once_and_names_the_admin(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 3);
        $uuid = (string) Str::uuid();
        $clicked = $this->createVerification($event, $verifiers[1], 'pending', $uuid);
        $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $this->createVerification($event, $verifiers[2], 'pending', $uuid);

        $this->post(route('event-verifications.approved', $clicked))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
        $this->assertSame(
            [$admin->id],
            $event->verifications()
                ->where('status', 'approved')
                ->pluck('verifier_id')
                ->map(fn ($verifierId) => (int) $verifierId)
                ->all()
        );
        $this->assertSame(
            [__('notification.request-verification.approved-finished-by', ['name' => $admin->full_name], $event->creator->language)],
            $this->verificationNotificationTitlesOf($event->creator)
        );
    }

    #[Test]
    public function an_admin_who_is_a_verifier_in_all_mode_confirms_the_event_from_the_inbox(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 1);
        $uuid = (string) Str::uuid();
        $own = $this->createVerification($event, $admin, 'pending', $uuid);
        $this->createVerification($event, $verifiers[0], 'pending', $uuid);

        $this->post(route('event-verifications.approved', $own))->assertSuccessful();

        $this->assertSame('approved', $own->fresh()->status);
        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
    }

    #[Test]
    public function an_admin_rejecting_a_foreign_entry_records_the_rejection_on_the_admin(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 2);
        $uuid = (string) Str::uuid();
        $clicked = $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $this->createVerification($event, $verifiers[1], 'pending', $uuid);

        $this->post(route('event-verifications.rejected', $clicked), ['rejection_reason' => 'Raum belegt'])
            ->assertSuccessful();

        $this->assertTrue((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
        $rejection = $event->verifications()->where('status', 'rejected')->sole();
        $this->assertSame($admin->id, (int) $rejection->verifier_id);
        $this->assertSame('Raum belegt', $rejection->rejection_reason);
        $this->assertSame(
            [__('notification.request-verification.user-rejected', ['name' => $admin->full_name], $event->creator->language)],
            $this->verificationNotificationTitlesOf($event->creator)
        );
    }

    #[Test]
    public function an_admin_can_reject_an_open_round_from_the_calendar_without_an_own_verification(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $uuid = (string) Str::uuid();
        $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $this->createVerification($event, $verifiers[1], 'pending', $uuid);

        $this->post(route('event-verifications.reject-by-events'), ['events' => [$event->id]])
            ->assertSessionMissing('error');

        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
        $this->assertSame($admin->id, (int) $event->verifications()->where('status', 'rejected')->sole()->verifier_id);
    }

    #[Test]
    public function a_verifier_finishing_an_any_mode_request_is_named_in_the_notification(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 2);
        $uuid = (string) Str::uuid();
        $own = $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $this->createVerification($event, $verifiers[1], 'pending', $uuid);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved', $own))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(1, $event->verifications()->count());
        $this->assertSame(
            [__(
                'notification.request-verification.approved-finished-by',
                ['name' => $verifiers[0]->full_name],
                $event->creator->language
            )],
            $this->verificationNotificationTitlesOf($event->creator)
        );
    }

    /**
     * @return array{0: Event, 1: \Illuminate\Database\Eloquent\Collection<int, User>}
     */
    private function planningEventWithVerifiers(string $mode, int $verifierCount): array
    {
        $verifiers = User::factory()->count($verifierCount)->create();
        $type = EventType::factory()->create(['verification_mode' => $mode]);
        $type->verifiers()->sync($verifiers->pluck('id'));
        $event = Event::factory()->create(['event_type_id' => $type->id, 'is_planning' => true]);

        return [$event, $verifiers];
    }

    private function createVerification(Event $event, User $verifier, string $status, string $uuid): EventVerification
    {
        return EventVerification::create([
            'uuid' => $uuid,
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => $status,
            'request_user_id' => $event->user_id,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function verificationNotificationTitlesOf(?User $user): array
    {
        $this->assertNotNull($user);

        return Notification::sent($user, EventNotification::class)
            ->map(fn (EventNotification $notification) => $notification->toArray())
            ->filter(fn ($data) => $data->type === NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS)
            ->map(fn ($data) => (string) $data->title)
            ->values()
            ->all();
    }
}
