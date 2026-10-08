<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/**
 * Tab "Team": Leute von hier ins Ticket-Haus einladen — tickets schreibt und verschickt.
 */
final class TicketingTeamTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));
    }

    private function connect(): TicketingConnection
    {
        return TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater-sued',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => '1',
            'connected_by_user_id' => User::factory()->create()->id,
        ]);
    }

    #[Test]
    public function the_team_tab_shows_every_person_with_their_state_in_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $member = User::factory()->create(['email' => 'erika@theater.test', 'first_name' => 'Erika', 'last_name' => 'Muster']);
        $invited = User::factory()->create(['email' => 'kai@theater.test']);
        $outside = User::factory()->create(['email' => 'neu@theater.test']);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/team' => Http::response(['team' => [
                ['email' => 'ERIKA@theater.test', 'name' => 'Erika Muster', 'preset' => 'admin', 'roles' => [], 'status' => 'member', 'expiresAt' => null],
                ['email' => 'kai@theater.test', 'name' => null, 'preset' => null, 'roles' => ['admission'], 'status' => 'invited', 'expiresAt' => '2026-09-19T10:00:00Z'],
            ]]),
        ]);

        $response = $this->get(route('settings.tickets.team'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Team')
                ->where('connection.connected', true)
                ->where('presets', ['admin', 'staff', 'door']));

        $people = collect($response->viewData('page')['props']['people'])->keyBy('id');

        $this->assertSame(['status' => 'member', 'preset' => 'admin'], [
            'status' => $people[$member->id]['status'], 'preset' => $people[$member->id]['preset'],
        ]);
        $this->assertSame(['status' => 'invited', 'preset' => 'custom'], [
            'status' => $people[$invited->id]['status'], 'preset' => $people[$invited->id]['preset'],
        ]);
        $this->assertNull($people[$outside->id]['status']);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/integration/v1/team')
            && $request->hasHeader('x-api-key', 'tk_plain'));
    }

    #[Test]
    public function inviting_sends_the_picked_people_with_one_preset_and_the_sender(): void
    {
        $this->connect();
        $sender = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $one = User::factory()->create(['email' => 'one@theater.test']);
        $two = User::factory()->create(['email' => 'two@theater.test']);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/team/invitations' => Http::response(['invitations' => [
                ['email' => 'one@theater.test', 'status' => 'invited'],
                ['email' => 'two@theater.test', 'status' => 'member'],
            ]]),
        ]);

        $this->post(route('settings.tickets.team.invite'), ['user_ids' => [$one->id, $two->id], 'preset' => 'staff'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(function (Request $request) use ($sender): bool {
            $emails = array_column($request['invitations'], 'email');
            sort($emails);

            return str_ends_with($request->url(), '/api/integration/v1/team/invitations')
                && $request->hasHeader('x-api-key', 'tk_plain')
                && $request['invitedBy']['email'] === $sender->email
                && $emails === ['one@theater.test', 'two@theater.test']
                && $request['invitations'][0]['preset'] === 'staff';
        });
    }

    #[Test]
    public function the_owner_preset_and_unknown_people_are_refused_before_any_call(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->from(route('settings.tickets.team'))
            ->post(route('settings.tickets.team.invite'), ['user_ids' => [999999], 'preset' => 'owner'])
            ->assertSessionHasErrors(['user_ids.0', 'preset']);

        Http::assertNothingSent();
    }

    #[Test]
    public function without_the_ticketing_permission_the_tab_is_closed(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('settings.tickets.team'))->assertForbidden();
    }
}
