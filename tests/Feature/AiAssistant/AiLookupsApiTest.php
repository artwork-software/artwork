<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-api-lookups: GET /ai/rooms, GET /ai/event-types (Konzept B1 §3, W1 §1).
 */
final class AiLookupsApiTest extends TestCase
{
    use GrantsAiAccess;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();

        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function names(array $payload): array
    {
        return array_values(array_map(
            static fn (array $row): string => (string) $row['name'],
            $payload['data']
        ));
    }

    // --- Scope -------------------------------------------------------------------------------

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/rooms')->assertForbidden();
        $this->getJson('/api/v1/ai/event-types')->assertForbidden();
    }

    #[Test]
    public function without_a_token_both_lookups_are_unauthorized(): void
    {
        $this->getJson('/api/v1/ai/rooms')->assertUnauthorized();
        $this->getJson('/api/v1/ai/event-types')->assertUnauthorized();
    }

    // --- Raeume ------------------------------------------------------------------------------

    #[Test]
    public function the_search_matches_a_part_of_the_name_regardless_of_case(): void
    {
        Room::factory()->create(['name' => 'Studio 1']);
        Room::factory()->create(['name' => 'Studio 2']);
        Room::factory()->create(['name' => 'Werkstatt']);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?q=stud')->assertOk()->json();

        $this->assertSame(['Studio 1', 'Studio 2'], $this->names($payload));
        $this->assertSame(2, $payload['total']);
        $this->assertFalse($payload['truncated']);
    }

    #[Test]
    public function a_room_carries_its_area(): void
    {
        $area = Area::factory()->create(['name' => 'Grosses Haus']);
        $room = Room::factory()->create(['name' => 'Studio 1', 'area_id' => $area->id]);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?q=' . rawurlencode('Studio 1'))->assertOk()->json();

        $this->assertSame($room->id, $payload['data'][0]['id']);
        $this->assertSame($area->id, $payload['data'][0]['area']['id']);
        $this->assertSame('Grosses Haus', $payload['data'][0]['area']['name']);
    }

    /**
     * `Room::scopeVisibleFor` (`Room.php:332`) gibt jeder angemeldeten Person jeden Raum — so wie
     * die Oberflaeche es tut (core-visibility-scopes). Geprueft wird deshalb, dass der Endpunkt
     * ohne jedes Hausrecht vollstaendig antwortet; enger wird er automatisch, sobald der Scope
     * eine Zeilenregel bekommt.
     */
    #[Test]
    public function a_person_without_any_permission_sees_the_rooms(): void
    {
        Room::factory()->create(['name' => 'Studio 1']);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?q=Studio')->assertOk()->json();

        $this->assertSame(['Studio 1'], $this->names($payload));
    }

    #[Test]
    public function the_room_list_stops_at_the_limit_and_says_so(): void
    {
        foreach (range(1, 4) as $number) {
            Room::factory()->create(['name' => 'Probe ' . $number]);
        }

        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?q=Probe&limit=2')->assertOk()->json();

        $this->assertSame(['Probe 1', 'Probe 2'], $this->names($payload));
        $this->assertSame(4, $payload['total']);
        $this->assertSame(2, $payload['limit']);
        $this->assertTrue($payload['truncated']);
    }

    /**
     * Ueber `ai.list_limit` kommt niemand hinaus — auch nicht mit einem eigenen `limit`.
     */
    #[Test]
    public function the_requested_limit_never_exceeds_the_configured_maximum(): void
    {
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?limit=500')->assertOk()->json();

        $this->assertSame((int) config('ai.list_limit'), $payload['limit']);
    }

    #[Test]
    public function the_room_search_treats_a_wildcard_as_text(): void
    {
        Room::factory()->create(['name' => 'Studio 1']);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms?q=' . rawurlencode('%'))->assertOk()->json();

        $this->assertSame([], $payload['data']);
        $this->assertSame(0, $payload['total']);
    }

    // --- Terminarten -------------------------------------------------------------------------

    #[Test]
    public function exactly_one_event_type_is_the_default(): void
    {
        EventType::factory()->create(['name' => 'Aufbau']);
        $fallback = EventType::factory()->create(['name' => 'Vorstellung', 'fallback_type' => true]);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/event-types')->assertOk()->json();

        $defaults = array_values(array_filter(
            $payload['data'],
            static fn (array $row): bool => $row['is_default'] === true
        ));

        $this->assertCount(1, $defaults);
        $this->assertSame($fallback->id, $defaults[0]['id']);
        // Der Default steht vorn, damit er auch bei mehr Terminarten als `limit` mitkommt.
        $this->assertSame($fallback->id, $payload['data'][0]['id']);
    }

    #[Test]
    public function without_a_marked_type_the_first_one_is_the_default(): void
    {
        EventType::query()->update(['fallback_type' => false]);
        EventType::factory()->create(['name' => 'Probe']);
        EventType::factory()->create(['name' => 'Aufbau']);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/event-types')->assertOk()->json();

        $defaults = array_values(array_filter(
            $payload['data'],
            static fn (array $row): bool => $row['is_default'] === true
        ));

        $names = $this->names($payload);
        sort($names);

        $this->assertCount(1, $defaults);
        // Ohne Kennzeichnung ist es die alphabetisch erste — und die steht an erster Stelle.
        $this->assertSame($payload['data'][0]['id'], $defaults[0]['id']);
        $this->assertSame($names[0], $payload['data'][0]['name']);
    }

    #[Test]
    public function an_event_type_carries_name_and_abbreviation(): void
    {
        $type = EventType::factory()->create(['name' => 'Generalprobe', 'abbreviation' => 'GP']);
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/event-types?q=General')->assertOk()->json();

        $this->assertSame($type->id, $payload['data'][0]['id']);
        $this->assertSame('Generalprobe', $payload['data'][0]['name']);
        $this->assertSame('GP', $payload['data'][0]['abbreviation']);
    }

    #[Test]
    public function the_event_type_list_stops_at_the_limit_and_says_so(): void
    {
        foreach (range(1, 3) as $number) {
            EventType::factory()->create(['name' => 'Sonderform ' . $number]);
        }

        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/event-types?q=Sonderform&limit=1')->assertOk()->json();

        $this->assertCount(1, $payload['data']);
        $this->assertSame(3, $payload['total']);
        $this->assertTrue($payload['truncated']);
    }
}
