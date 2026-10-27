<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** Der Einlass aus den Ticketdetails: einchecken, zurücknehmen und Karteninhaber einlassen — über tickets. */
final class TicketingDoorTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const DATE_ID = '7a2e3d4c-5b6f-4a71-9b82-0c3d4e5f6a71';
    private const TICKET_ID = '1b2c3d4e-5f60-4a71-8b92-0c1d2e3f4a5b';
    private const PASS_ID = '2c3d4e5f-6a71-4b82-9c03-1d2e3f4a5b6c';
    private const CATEGORY_ID = '3d4e5f6a-7b82-4c93-8d14-2e3f4a5b6c7d';
    private const ADMISSION_ID = '4e5f6a7b-8c93-4da4-9e25-3f4a5b6c7d8e';
    private const BASE = self::TICKETS_URL . '/api/integration/v1/dates/' . self::DATE_ID;

    private User $admin;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));

        TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => 1,
            'connected_by_user_id' => null,
        ]);

        $this->admin = $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Hamlet']);
        $type = EventType::factory()->create(['relevant_for_ticketing' => true]);
        $this->event = Event::factory()->create(['project_id' => $project->id, 'event_type_id' => $type->id]);
        TicketingEventRelease::query()->create([
            'event_id' => $this->event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);
    }

    #[Test]
    public function checking_in_names_the_ticket_and_the_person_at_the_door(): void
    {
        Http::fake([
            self::BASE . '/check-ins' => Http::response(['outcome' => 'accepted', 'checkedInAt' => '2027-02-01T19:12:00.000Z']),
            self::BASE . '/check-ins/revert' => Http::response(['reverted' => true]),
        ]);

        $this->postJson(route('ticketing.door.check-in', $this->event), ['ticket_id' => self::TICKET_ID])
            ->assertOk()
            ->assertJsonPath('outcome', 'accepted');
        $this->postJson(route('ticketing.door.revert', $this->event), ['ticket_id' => self::TICKET_ID])
            ->assertOk()
            ->assertJsonPath('reverted', true);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE . '/check-ins'
            && $request['ticketId'] === self::TICKET_ID
            && $request['actor']['email'] === $this->admin->email
            && $request['actor']['name'] === $this->admin->full_name);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE . '/check-ins/revert');
    }

    #[Test]
    public function the_pass_lookup_and_admission_go_to_tickets(): void
    {
        Http::fake([
            self::BASE . '/passes*' => Http::response(['seated' => false, 'categories' => [], 'passes' => []]),
            self::BASE . '/pass-admissions' => Http::response([
                'orderNumber' => 'ATK-2027-000001', 'ticketCode' => 'ABCD1234', 'paidCents' => 400, 'checkedInAt' => '2027-02-01T19:12:00.000Z',
            ]),
        ]);

        $this->getJson(route('ticketing.door.passes', ['event' => $this->event, 'q' => 'Ada']))
            ->assertOk()
            ->assertJsonPath('seated', false);
        $this->postJson(route('ticketing.door.admit', $this->event), [
            'admission_id' => self::ADMISSION_ID, 'pass_id' => self::PASS_ID, 'price_category_id' => self::CATEGORY_ID,
        ])
            ->assertOk()
            ->assertJsonPath('paidCents', 400);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), self::BASE . '/passes')
            && $request['q'] === 'Ada');
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE . '/pass-admissions'
            && $request['admissionId'] === self::ADMISSION_ID
            && $request['passId'] === self::PASS_ID
            && $request['priceCategoryId'] === self::CATEGORY_ID
            && $request['actor']['email'] === $this->admin->email);
    }

    #[Test]
    public function a_refusal_from_tickets_comes_back_in_words(): void
    {
        Http::fake([
            self::BASE . '/pass-admissions' => Http::response(['error' => ['code' => 'PASS_EXHAUSTED', 'message' => 'PASS_EXHAUSTED']], 409),
        ]);

        $this->postJson(route('ticketing.door.admit', $this->event), [
            'admission_id' => self::ADMISSION_ID, 'pass_id' => self::PASS_ID, 'price_category_id' => self::CATEGORY_ID,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('This pass has no uses left.'));
    }

    #[Test]
    public function an_unreleased_date_has_no_door(): void
    {
        Http::fake();
        $this->event->ticketingRelease->update(['state' => 'withdrawn']);

        $this->postJson(route('ticketing.door.check-in', $this->event), ['ticket_id' => self::TICKET_ID])
            ->assertStatus(422)
            ->assertJsonPath('message', __('This date is not released for sale.'));

        Http::assertNothingSent();
    }

    #[Test]
    public function the_door_needs_the_admission_permission(): void
    {
        Http::fake();
        $this->actingAsUserWith([]);

        $this->postJson(route('ticketing.door.check-in', $this->event), ['ticket_id' => self::TICKET_ID])->assertForbidden();
        $this->getJson(route('ticketing.door.passes', $this->event))->assertForbidden();

        Http::assertNothingSent();
    }
}
