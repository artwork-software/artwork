<?php

namespace Tests\Feature;

use Artwork\Modules\AppApi\Services\AppTokenService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventComment;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserDeactivationService;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;

/**
 * Entscheidungen aus dem Sicherheits-Audit vom 18.11.2026: Papierkorb leeren nur für löschbare Termine,
 * Kommentar als Raumverwaltung nur mit Antwortrecht, Deaktivierung entwertet App-Tokens und
 * „Angemeldet bleiben“.
 */
final class SecurityAuditDecisions20261118RegressionTest extends FeatureTestCase
{
    #[Test]
    public function emptying_the_event_trash_keeps_events_the_user_may_not_delete(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::TRASH_ACCESS]);
        $own = Event::factory()->create(['user_id' => $user->id]);
        $foreign = Event::factory()->create(['user_id' => User::factory()->create()->id]);
        $own->delete();
        $foreign->delete();

        $this->delete(route('events.force.all'))
            ->assertRedirect(route('events.trashed'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('events', ['id' => $own->id]);
        $this->assertSoftDeleted('events', ['id' => $foreign->id]);
    }

    #[Test]
    public function an_admin_still_empties_the_whole_event_trash(): void
    {
        $this->actingAsAdmin();
        $first = Event::factory()->create();
        $second = Event::factory()->create();
        $first->delete();
        $second->delete();

        $this->delete(route('events.force.all'))
            ->assertRedirect(route('events.trashed'))
            ->assertSessionMissing('error');

        $this->assertDatabaseMissing('events', ['id' => $first->id]);
        $this->assertDatabaseMissing('events', ['id' => $second->id]);
    }

    #[Test]
    public function a_booking_comment_of_the_requester_is_no_message_of_the_room_administration(): void
    {
        Notification::fake();
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $manager = User::factory()->create();
        $event = $this->roomRequest($requester, $manager);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'adminComment' => 'Bitte bestätigen',
        ]))->assertSuccessful();

        $comment = EventComment::query()->where('event_id', $event->id)->firstOrFail();
        $this->assertFalse((bool) $comment->is_admin_comment);
        Notification::assertNotSentTo($manager, RoomRequestNotification::class);
    }

    #[Test]
    public function a_booking_comment_of_someone_who_may_answer_the_request_stays_a_room_administration_message(): void
    {
        Notification::fake();
        $requester = User::factory()->create();
        $manager = User::factory()->create();
        $event = $this->roomRequest($requester, $manager);
        $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'adminComment' => 'Raum ist frei',
        ]))->assertSuccessful();

        $comment = EventComment::query()->where('event_id', $event->id)->firstOrFail();
        $this->assertTrue((bool) $comment->is_admin_comment);
        Notification::assertSentTo($requester, RoomRequestNotification::class);
    }

    #[Test]
    public function deactivating_an_account_revokes_app_tokens_and_the_remember_token(): void
    {
        if (!is_readable(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

        $user = User::factory()->create(['remember_token' => 'altes-geraet']);
        app(AppTokenService::class)->issue($user, 'Verlorenes Handy');
        $machineKey = $user->createToken('Schnittstelle', ['calendar:read'])->getToken();

        app(UserDeactivationService::class)->deactivate($user, User::factory()->create());
        app(UserDeactivationService::class)->reactivate($user->fresh());

        $user->refresh();
        $this->assertNotSame('altes-geraet', $user->remember_token);
        $appTokens = $user->tokens()->get()->filter(
            static fn ($token): bool => in_array(AppTokenService::SCOPE, (array) $token->scopes, true)
        );
        $this->assertCount(1, $appTokens);
        $this->assertTrue((bool) $appTokens->first()->revoked);
        $this->assertFalse((bool) $machineKey->fresh()->revoked);
    }

    private function roomRequest(User $requester, User $manager): Event
    {
        $project = Project::factory()->create();
        $project->users()->attach($manager->id, ['is_manager' => true]);

        return Event::factory()->create([
            'user_id' => $requester->id,
            'project_id' => $project->id,
            'room_id' => Room::factory()->create()->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Event $event, array $overrides = []): array
    {
        return array_replace([
            'start' => $event->start_time->format('Y-m-d H:i'),
            'end' => $event->end_time->format('Y-m-d H:i'),
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $event->event_type_id,
            'roomId' => $event->room_id,
            'projectId' => $event->project_id,
            'title' => $event->name,
            'eventName' => $event->eventName,
            'isOption' => true,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'isPlanning' => false,
            'noNotifications' => false,
        ], $overrides);
    }
}
