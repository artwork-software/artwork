<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * "Genehmigen" im Planungskalender (Entscheidung 20.10.2026): Admins übernehmen geplante Termine direkt fest, mit
 * oder ohne offene Prüfrunde; alleinige Prüfende genehmigen auch ohne vorherige Anfrage.
 */
final class EventVerificationDirectApprovalTest extends FeatureTestCase
{
    #[Test]
    public function an_admin_approves_a_planned_event_without_request_directly(): void
    {
        $admin = $this->actingAsAdmin();
        [$event] = $this->planningEventWithVerifiers('all', 2);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionMissing('error');

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(1, $event->verifications()->count());
        $verification = $event->verifications()->first();
        $this->assertSame('approved', $verification->status);
        $this->assertSame($admin->id, (int) $verification->verifier_id);
    }

    #[Test]
    public function an_admin_decides_an_open_all_mode_round_alone(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $uuid = (string) Str::uuid();
        $this->createVerification($event, $verifiers[0], 'approved', $uuid);
        $this->createVerification($event, $verifiers[1], 'pending', $uuid);

        $this->post(route('event-verifications.approved-by-event', $event))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
        $this->assertTrue($event->verifications()
            ->where('uuid', $uuid)
            ->where('verifier_id', $admin->id)
            ->where('status', 'approved')
            ->exists());
    }

    #[Test]
    public function an_admin_approving_a_foreign_verification_in_the_inbox_confirms_the_event(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $uuid = (string) Str::uuid();
        $first = $this->createVerification($event, $verifiers[0], 'pending', $uuid);
        $this->createVerification($event, $verifiers[1], 'pending', $uuid);

        $this->post(route('event-verifications.approved', $first))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->where('status', 'pending')->count());
    }

    #[Test]
    public function an_admin_who_is_one_of_the_verifiers_approves_the_own_verification_and_confirms(): void
    {
        $admin = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 1);
        $uuid = (string) Str::uuid();
        $own = $this->createVerification($event, $admin, 'pending', $uuid);
        $this->createVerification($event, $verifiers[0], 'pending', $uuid);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionMissing('error');

        $this->assertSame('approved', $own->fresh()->status);
        $this->assertSame(1, $event->verifications()->count());
        $this->assertFalse((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function an_admin_cannot_approve_a_fixed_event(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $event->fresh()->occupancy_option);
        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function approving_a_single_tile_that_cannot_be_approved_reports_it(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);

        $this->post(route('event-verifications.approved-by-event', $event))
            ->assertSessionHas(
                'error',
                __('This event could not be approved: it is already approved or you may not approve it. '
                    . 'Reload the page to see the current state.')
            );

        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function the_specific_verifier_approves_an_own_planned_event_without_request(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('specific', 1);
        $event->update(['user_id' => $verifiers[0]->id]);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionMissing('error');

        $this->assertFalse((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function an_any_mode_verifier_with_planning_edit_rights_approves_a_foreign_event_without_request(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 2);
        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR], $verifiers[0]);

        $this->post(route('event-verifications.approved-by-event', $event))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function a_verifier_may_not_approve_a_foreign_event_without_request(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function an_all_mode_verifier_cannot_approve_alone_without_request(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $event->update(['user_id' => $verifiers[0]->id]);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$event->id]])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $event->fresh()->is_planning);
        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function event_types_approved_on_request_are_listed_per_person(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $none = EventType::factory()->create(['verification_mode' => 'none']);
        $specific = EventType::factory()->create([
            'verification_mode' => 'specific',
            'specific_verifier_id' => $user->id,
        ]);
        $foreignSpecific = EventType::factory()->create([
            'verification_mode' => 'specific',
            'specific_verifier_id' => $other->id,
        ]);
        $any = EventType::factory()->create(['verification_mode' => 'any']);
        $any->verifiers()->sync([$user->id]);
        $all = EventType::factory()->create(['verification_mode' => 'all']);
        $all->verifiers()->sync([$user->id]);

        $ids = app(EventVerificationService::class)->eventTypeIdsApprovedOnRequestBy($user)->all();

        $this->assertContains($none->id, $ids);
        $this->assertContains($specific->id, $ids);
        $this->assertContains($any->id, $ids);
        $this->assertNotContains($foreignSpecific->id, $ids);
        $this->assertNotContains($all->id, $ids);
    }

    /**
     * @return array{0: Event, 1: \Illuminate\Database\Eloquent\Collection<int, User>}
     */
    private function planningEventWithVerifiers(string $mode, int $verifierCount): array
    {
        $verifiers = User::factory()->count($verifierCount)->create();
        $type = EventType::factory()->create([
            'verification_mode' => $mode,
            'specific_verifier_id' => $mode === 'specific' ? $verifiers->last()?->id : null,
        ]);
        $type->verifiers()->sync($verifiers->pluck('id'));
        $event = Event::factory()->create(['event_type_id' => $type->id, 'is_planning' => true]);

        return [$event, $verifiers];
    }

    private function createVerification(
        Event $event,
        User $verifier,
        string $status = 'pending',
        ?string $uuid = null,
    ): EventVerification {
        return EventVerification::create([
            'uuid' => $uuid ?? (string) Str::uuid(),
            'event_id' => $event->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => $status,
            'request_user_id' => $event->user_id,
        ]);
    }
}
