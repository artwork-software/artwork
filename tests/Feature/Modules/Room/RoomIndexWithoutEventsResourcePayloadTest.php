<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Datenvertrag der Raumliste, die Benachrichtigungen, Raumverwaltung und Papierkorb
 * ausliefern: Admins nur als id/Name/Avatar (Raumadmin-Check im Termin-Modal,
 * Anfrage-Dialog, RoomSidenav), Ersteller ebenso (Papierkorb). Vorher lud die Resource
 * je Raum den Ersteller und zweimal die Admins nach und serialisierte diese über
 * UserIndexResource inklusive Schichten und Gewerken (Benachrichtigungen: 734 Queries).
 */
final class RoomIndexWithoutEventsResourcePayloadTest extends FeatureTestCase
{
    private const SLIM_USER_FIELDS = ['id', 'first_name', 'last_name', 'profile_photo_url'];

    private Room $room;
    private User $creator;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create();
        $this->admin = User::factory()->create();
        $member = User::factory()->create();

        $this->room = Room::factory()->create(['user_id' => $this->creator->id]);
        $this->room->users()->attach($this->admin->id, ['is_admin' => true, 'can_request' => false]);
        $this->room->users()->attach($member->id, ['is_admin' => false, 'can_request' => true]);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function assertSlimUser(array $user, User $expected, string $label): void
    {
        $this->assertSame(self::SLIM_USER_FIELDS, array_keys($user), "$label: genau die schlanken Felder");
        $this->assertSame($expected->id, $user['id']);
        $this->assertSame($expected->first_name, $user['first_name']);
        $this->assertSame($expected->last_name, $user['last_name']);
        $this->assertNotSame('', (string) $user['profile_photo_url']);
    }

    #[Test]
    public function admins_and_creator_are_slim_user_objects(): void
    {
        $payload = RoomIndexWithoutEventsResource::make(Room::query()->findOrFail($this->room->id))->resolve();

        $this->assertSame($this->room->id, $payload['id']);
        $this->assertSame($this->room->name, $payload['name']);

        $this->assertCount(1, $payload['admins'], 'nur is_admin-Personen');
        $this->assertSlimUser($payload['admins'][0], $this->admin, 'admins');
        // room_admins und admins sind dieselbe Liste (verschiedene Konsumenten lesen verschiedene Keys)
        $this->assertSame($payload['admins'], $payload['room_admins']);

        $this->assertSlimUser($payload['created_by'], $this->creator, 'created_by');
    }

    #[Test]
    public function serializing_all_rooms_runs_no_query_per_room(): void
    {
        foreach (range(1, 4) as $i) {
            $room = Room::factory()->create(['user_id' => $this->creator->id]);
            $room->users()->attach($this->admin->id, ['is_admin' => true, 'can_request' => false]);
        }

        $rooms = Room::all();

        DB::flushQueryLog();
        DB::enableQueryLog();
        RoomIndexWithoutEventsResource::collection($rooms)->resolve();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(
            0,
            $queries,
            'Resource darf nicht nachladen: ' . json_encode(array_column($queries, 'query'))
        );
    }

    #[Test]
    public function event_types_with_verifiers_serialize_without_query_per_type(): void
    {
        $verifier = User::factory()->create();
        EventType::factory()->count(3)->create()
            ->each(fn (EventType $type) => $type->verifiers()->attach($verifier->id));

        $eventTypes = app(EventTypeService::class)->getAllWithVerifiers();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $payload = EventTypeResource::collection($eventTypes)->resolve();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(
            0,
            $queries,
            'Verifizierer müssen vorgeladen sein: ' . json_encode(array_column($queries, 'query'))
        );
        $this->assertSame($verifier->id, collect($payload)->last()['users'][0]['id']);
    }

    #[Test]
    public function notifications_page_delivers_rooms_with_slim_admins(): void
    {
        $this->actingAsAdmin();

        $this->get('/notifications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('rooms')
                ->has('eventTypes')
                ->where('rooms', function ($rooms): bool {
                    $room = collect($rooms)->firstWhere('id', $this->room->id);
                    $this->assertNotNull($room, 'Raum fehlt in der rooms-Prop');
                    $this->assertSlimUser($room['admins'][0], $this->admin, 'Prop-admins');
                    $this->assertSlimUser($room['created_by'], $this->creator, 'Prop-created_by');

                    return true;
                }));
    }
}
