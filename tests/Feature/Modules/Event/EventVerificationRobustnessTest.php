<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Durchlauf Planungskalender 14.10.2026: Prüf-Endpunkte vertragen veraltete Ansichten, Doppelklicks, gelöschte
 * Termine/Prüfende und feste Termine in der Mehrfachauswahl.
 */
final class EventVerificationRobustnessTest extends FeatureTestCase
{
    #[Test]
    public function a_fixed_room_request_cannot_be_confirmed_through_the_verification_endpoint(): void
    {
        $this->actingAsAdmin();
        $type = EventType::factory()->create(['verification_mode' => 'none']);
        $event = Event::factory()->create([
            'event_type_id' => $type->id,
            'is_planning' => false,
            'occupancy_option' => true,
        ]);

        $this->post(route('events.sendToVerification', $event))
            ->assertSessionHas('error', __('Only planned events can be sent for verification.'));

        $this->assertTrue((bool) $event->fresh()->occupancy_option);
    }

    #[Test]
    public function requesting_verification_without_verifiers_reports_it_instead_of_doing_nothing(): void
    {
        $this->actingAsAdmin();
        [$event] = $this->planningEventWithVerifiers('all', 0);

        $this->post(route('events.sendToVerification', $event))
            ->assertSessionHas('error', __('No verifiers are assigned to the event type of this event.'));

        $this->assertSame(0, $event->verifications()->count());
        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function a_deleted_specific_verifier_counts_as_missing(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('specific', 1);
        $verifiers[0]->delete();

        $this->assertTrue(app(EventVerificationService::class)->lacksVerifiers($event->fresh()));
    }

    #[Test]
    public function requesting_verification_twice_creates_only_one_round(): void
    {
        $this->actingAsAdmin();
        [$event] = $this->planningEventWithVerifiers('all', 2);

        $this->post(route('events.sendToVerification', $event));
        $this->post(route('events.sendToVerification', $event));

        $this->assertSame(2, $event->verifications()->count());
        $this->assertSame(1, $event->verifications()->distinct('uuid')->count('uuid'));
    }

    #[Test]
    public function bulk_request_skips_fixed_and_deleted_events(): void
    {
        $this->actingAsAdmin();
        [$planningEvent] = $this->planningEventWithVerifiers('all', 1);
        $fixedEvent = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);

        $this->post(route('events-verifications.request-verification'), [
            'events' => [$planningEvent->id, $fixedEvent->id, 999999],
        ])->assertSuccessful();

        $this->assertSame(1, $planningEvent->verifications()->count());
        $this->assertTrue((bool) $fixedEvent->fresh()->occupancy_option);
    }

    #[Test]
    public function approving_an_already_rejected_verification_is_a_no_op(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0], 'rejected');

        $this->post(route('event-verifications.approved', $verification))->assertSuccessful();

        $this->assertSame('rejected', $verification->fresh()->status);
        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function a_verifier_cannot_answer_the_verification_of_another_verifier(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $foreignVerification = $this->createVerification($event, $verifiers[1]);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved', $foreignVerification))->assertForbidden();
        $this->post(route('event-verifications.rejected', $foreignVerification))->assertForbidden();

        $this->assertSame('pending', $foreignVerification->fresh()->status);
    }

    #[Test]
    public function a_verifier_can_answer_the_own_verification(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.approved', $verification))->assertSuccessful();

        $this->assertSame('approved', $verification->fresh()->status);
        $this->assertFalse((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function answering_a_verification_of_a_trashed_event_returns_not_found(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);
        $event->delete();

        $this->post(route('event-verifications.approved', $verification))->assertNotFound();
    }

    #[Test]
    public function rejection_reason_must_be_a_string(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);

        $this->postJson(route('event-verifications.rejected', $verification), [
            'rejection_reason' => ['nested' => 'array'],
        ])->assertStatus(422);

        $this->assertSame('pending', $verification->fresh()->status);
    }

    #[Test]
    public function bulk_endpoints_validate_and_skip_deleted_events(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);
        $this->actingAs($verifiers[0]);

        $this->postJson(route('event-verifications.approved-by-events'), ['events' => ['abc']])
            ->assertStatus(422);
        $this->post(route('event-verifications.approved-by-events'), ['events' => [999999, $event->id]])
            ->assertSuccessful();

        $this->assertSame('approved', $verification->fresh()->status);
    }

    #[Test]
    public function bulk_approve_reports_events_the_user_may_not_verify_instead_of_skipping_them_silently(): void
    {
        [$ownEvent, $ownVerifiers] = $this->planningEventWithVerifiers('any', 1);
        [$foreignEvent, $foreignVerifiers] = $this->planningEventWithVerifiers('any', 1);
        $ownVerification = $this->createVerification($ownEvent, $ownVerifiers[0]);
        $foreignVerification = $this->createVerification($foreignEvent, $foreignVerifiers[0]);
        $this->actingAs($ownVerifiers[0]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$ownEvent->id, $foreignEvent->id]])
            ->assertSessionHas('error');

        $this->assertSame('approved', $ownVerification->fresh()->status);
        $this->assertSame('pending', $foreignVerification->fresh()->status);
    }

    #[Test]
    public function bulk_approve_without_any_own_verification_changes_nothing(): void
    {
        [$foreignEvent, $foreignVerifiers] = $this->planningEventWithVerifiers('any', 1);
        $foreignVerification = $this->createVerification($foreignEvent, $foreignVerifiers[0]);
        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);

        $this->post(route('event-verifications.approved-by-events'), ['events' => [$foreignEvent->id]])
            ->assertSessionHas('error');
        $this->post(route('event-verifications.reject-by-events'), ['events' => [$foreignEvent->id]])
            ->assertSessionHas('error');

        $this->assertSame('pending', $foreignVerification->fresh()->status);
    }

    #[Test]
    public function bulk_reject_of_own_verifications_reports_no_error(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);
        $this->actingAs($verifiers[0]);

        $this->post(route('event-verifications.reject-by-events'), [
            'events' => [$event->id],
            'rejection_reason' => 'Raum belegt',
        ])->assertSessionMissing('error');

        $this->assertSame('rejected', $verification->fresh()->status);
    }

    #[Test]
    public function withdrawing_without_open_round_does_not_turn_a_confirmed_event_back_into_a_planned_one(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $this->createVerification($event, $verifiers[0], 'approved');
        $event->update(['is_planning' => false]);

        $this->post(route('event-verifications.cancel-verification', $event))->assertSuccessful();

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(1, $event->verifications()->count());
    }

    #[Test]
    public function withdrawing_tolerates_a_deleted_verifier(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $verification = $this->createVerification($event, $verifiers[0]);
        $verification->update(['verifier_id' => 999999]);

        $this->post(route('event-verifications.cancel-verification', $event))->assertSuccessful();

        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function planning_calendar_editors_can_withdraw_like_they_can_request(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $this->createVerification($event, $verifiers[0]);
        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR]);

        $this->post(route('event-verifications.cancel-verification', $event))->assertSuccessful();

        $this->assertSame(0, $event->verifications()->count());
    }

    #[Test]
    public function sent_requests_use_the_latest_round_and_tolerate_deleted_verifiers(): void
    {
        $requester = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('specific', 2);
        $event->update(['user_id' => $requester->id]);

        // Alte Runde abgelehnt, neue Runde offen – Anlagezeitpunkte so gewählt, dass der übersetzte
        // Wochentag ("Dienstag" < "Mittwoch") die alte Runde nach vorn sortiert hätte
        $this->travelTo('2026-10-14 09:00:00');
        $this->createVerification($event, $verifiers[0], 'rejected', 'old-round');
        $this->travelTo('2026-10-20 09:00:00');
        $current = $this->createVerification($event, $verifiers[1], 'pending', 'new-round');
        $orphan = $this->createVerification($event, $verifiers[0], 'pending', 'new-round');
        $orphan->update(['verifier_id' => 999999]);
        $this->travelBack();

        $myRequest = app(EventVerificationService::class)->getAllRequestedByUser($requester)->first();

        $this->assertSame(2, $myRequest->verification_status_counts['pending']);
        $this->assertSame(0, $myRequest->verification_status_counts['rejected']);
        $pendingVerifiers = $myRequest->verifier_grouped_by_status['pending'];
        $this->assertCount(1, $pendingVerifiers);
        $this->assertSame($current->verifier_id, $pendingVerifiers[0]->id);
    }

    #[Test]
    public function converting_an_open_room_request_to_planning_withdraws_the_request(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);

        $this->post(route('events.convertToPlanning', $event))->assertRedirect();

        $event->refresh();
        $this->assertTrue((bool) $event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function converting_a_project_to_planning_withdraws_open_room_requests(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);

        $this->post(route('projects.convert-to-planning', $event->project_id))->assertSuccessful();

        $event->refresh();
        $this->assertTrue((bool) $event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function the_calendar_jump_of_a_planned_event_opens_the_planning_calendar(): void
    {
        $user = $this->actingAsAdmin();
        $event = Event::factory()->create([
            'is_planning' => true,
            'start_time' => '2026-10-15 10:00:00',
            'end_time' => '2026-10-15 12:00:00',
        ]);

        $this->get(route('event-verifications.redirect-to-calendar', $event))
            ->assertRedirect(route('planning-event-calendar.index', ['highlightEventId' => $event->id]));

        $planningFilter = $user->userFilters()->where('filter_type', UserFilterTypes::PLANNING_FILTER->value)->first();
        $this->assertSame('2026-10-12', $planningFilter->start_date?->format('Y-m-d'));
    }

    #[Test]
    public function the_calendar_jump_of_a_fixed_event_stays_in_the_calendar(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false]);

        $this->get(route('event-verifications.redirect-to-calendar', $event))
            ->assertRedirect(route('events', ['highlightEventId' => $event->id]));
    }

    #[Test]
    public function bulk_request_skips_foreign_events_of_viewers_and_reports_it(): void
    {
        [$foreignEvent] = $this->planningEventWithVerifiers('all', 1);
        [$ownEvent] = $this->planningEventWithVerifiers('all', 1);
        $viewer = $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $ownEvent->update(['user_id' => $viewer->id]);

        $this->post(route('events-verifications.request-verification'), [
            'events' => [$foreignEvent->id, $ownEvent->id],
        ])->assertSessionHas('error');

        $this->assertSame(0, $foreignEvent->verifications()->count());
        $this->assertSame(1, $ownEvent->verifications()->count());
    }

    /**
     * Release-Review 16.10.2026: jeder Termin der Auswahl überschrieb den Flash des vorigen – ein eigener Termin
     * ohne Prüfende ging neben dem Hinweis auf fremde Termine unter.
     */
    #[Test]
    public function bulk_request_reports_events_without_verifiers_together_with_skipped_foreign_events(): void
    {
        [$foreignEvent] = $this->planningEventWithVerifiers('all', 1);
        [$ownEventWithoutVerifiers] = $this->planningEventWithVerifiers('all', 0);
        $viewer = $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $ownEventWithoutVerifiers->update(['user_id' => $viewer->id]);

        $this->post(route('events-verifications.request-verification'), [
            'events' => [$ownEventWithoutVerifiers->id, $foreignEvent->id],
        ])->assertSessionHas('error', trans_choice(
            '{1} One event was not sent: no verifiers are assigned to its event type.|'
            . '[2,*] :count events were not sent: no verifiers are assigned to their event types.',
            1
        ) . ' ' . trans_choice(
            '{1} One event of someone else was skipped: '
            . 'you may only request verification for your own planned events.|'
            . '[2,*] :count events of others were skipped: '
            . 'you may only request verification for your own planned events.',
            1
        ));

        $this->assertSame(0, $foreignEvent->verifications()->count());
        $this->assertSame(0, $ownEventWithoutVerifiers->verifications()->count());
    }

    #[Test]
    public function bulk_request_counts_all_events_without_verifiers_in_one_message(): void
    {
        $this->actingAsAdmin();
        [$firstEvent] = $this->planningEventWithVerifiers('all', 0);
        [$secondEvent] = $this->planningEventWithVerifiers('any', 0);
        [$eventWithVerifier] = $this->planningEventWithVerifiers('all', 1);

        $this->post(route('events-verifications.request-verification'), [
            'events' => [$firstEvent->id, $secondEvent->id, $eventWithVerifier->id],
        ])->assertSessionHas('error', trans_choice(
            '{1} One event was not sent: no verifiers are assigned to its event type.|'
            . '[2,*] :count events were not sent: no verifiers are assigned to their event types.',
            2,
            ['count' => 2]
        ));

        $this->assertSame(1, $eventWithVerifier->verifications()->count());
    }

    #[Test]
    public function bulk_request_without_problems_reports_no_error(): void
    {
        $this->actingAsAdmin();
        [$event] = $this->planningEventWithVerifiers('all', 1);

        $this->post(route('events-verifications.request-verification'), ['events' => [$event->id]])
            ->assertSessionMissing('error');

        $this->assertSame(1, $event->verifications()->count());
    }

    /**
     * Release-Review 16.10.2026: die Projekt-Umstellung läuft je Termin – ein Abbruch mittendrin darf das Projekt
     * nicht halb umgestellt zurücklassen.
     */
    #[Test]
    public function converting_a_project_to_planning_is_all_or_nothing(): void
    {
        $this->actingAsAdmin();
        $firstEvent = Event::factory()->create(['is_planning' => false, 'occupancy_option' => true]);
        $secondEvent = Event::factory()->create([
            'project_id' => $firstEvent->project_id,
            'is_planning' => false,
            'occupancy_option' => true,
        ]);

        $realService = app(EventVerificationService::class);
        $calls = 0;
        $this->mock(EventVerificationService::class, function ($mock) use ($realService, &$calls): void {
            $mock->shouldReceive('convertToPlanning')->andReturnUsing(
                function (Event $event) use ($realService, &$calls): void {
                    if (++$calls === 2) {
                        throw new \RuntimeException('Abbruch mitten in der Umstellung');
                    }
                    $realService->convertToPlanning($event);
                }
            );
        });

        $this->post(route('projects.convert-to-planning', $firstEvent->project_id))->assertServerError();

        $this->assertSame(2, $calls);
        foreach ([$firstEvent, $secondEvent] as $event) {
            $event->refresh();
            $this->assertFalse((bool) $event->is_planning);
            $this->assertTrue((bool) $event->occupancy_option);
        }
    }

    #[Test]
    public function a_rejected_event_is_listed_as_planned_again_so_it_can_be_requested_again(): void
    {
        $requester = $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 1);
        $event->update(['user_id' => $requester->id]);
        $this->createVerification($event, $verifiers[0], 'rejected');

        $plannedEvents = app(EventVerificationService::class)->getPlannedEvents($requester);

        $this->assertSame([$event->id], $plannedEvents->pluck('id')->all());
    }

    #[Test]
    public function the_specific_verifier_gets_the_verify_actions_in_the_planning_calendar(): void
    {
        [$event, $verifiers] = $this->planningEventWithVerifiers('specific', 1);
        $event->event_type->verifiers()->detach();
        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR], $verifiers[0]);

        $this->get(route('planning-event-calendar.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('verifierForEventTypIds', [$event->event_type_id]));
    }

    #[Test]
    public function live_updates_of_planned_events_keep_admission_time_and_series(): void
    {
        $this->actingAsAdmin();
        [$event] = $this->planningEventWithVerifiers('none', 0);
        $event->update(['admission_time' => '18:30:00']);

        $dto = \Artwork\Modules\Calendar\DTO\BroadcastEventDTOWithVerifications::formModel($event->fresh());

        $this->assertSame('18:30', $dto->admission_time);
        $this->assertNull($dto->series_id);
    }

    /**
     * Entscheidung 14.10.2026: im Modus "alle" schließt eine Ablehnung die Runde.
     */
    #[Test]
    public function a_rejection_in_all_mode_closes_the_open_verifications_of_the_round(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 3);
        $rejected = $this->createVerification($event, $verifiers[0], 'pending', 'round');
        $this->createVerification($event, $verifiers[1], 'approved', 'round');
        $this->createVerification($event, $verifiers[2], 'pending', 'round');
        $this->createVerification($event, $verifiers[2], 'pending', 'other-round');

        app(EventVerificationService::class)->rejectVerification($rejected, 'Raum belegt');

        $this->assertSame(
            ['approved', 'rejected'],
            $event->verifications()->where('uuid', 'round')->orderBy('status')->pluck('status')->all()
        );
        $this->assertSame(1, $event->verifications()->where('uuid', 'other-round')->count());
    }

    #[Test]
    public function a_rejection_in_any_mode_leaves_the_other_verifiers_pending(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('any', 2);
        $rejected = $this->createVerification($event, $verifiers[0], 'pending', 'round');
        $this->createVerification($event, $verifiers[1], 'pending', 'round');

        app(EventVerificationService::class)->rejectVerification($rejected, '');

        $this->assertSame(1, $event->verifications()->where('status', 'pending')->count());
    }

    /**
     * Entscheidung 14.10.2026: Zurückziehen nimmt nur die offene Runde zurück, frühere Runden bleiben als Verlauf.
     */
    #[Test]
    public function withdrawing_keeps_earlier_rounds_as_history(): void
    {
        $this->actingAsAdmin();
        [$event, $verifiers] = $this->planningEventWithVerifiers('all', 2);
        $earlier = $this->createVerification($event, $verifiers[0], 'rejected', 'earlier-round');
        $this->createVerification($event, $verifiers[0], 'approved', 'open-round');
        $this->createVerification($event, $verifiers[1], 'pending', 'open-round');

        $this->post(route('event-verifications.cancel-verification', $event))->assertSuccessful();

        $this->assertSame([$earlier->id], $event->verifications()->pluck('id')->all());
        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function viewing_the_planning_calendar_alone_does_not_allow_creating_events(): void
    {
        $viewer = $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);

        $this->assertFalse($viewer->can('create', Event::class));
    }

    /**
     * @return array{0: Event, 1: \Illuminate\Support\Collection<int, User>}
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
