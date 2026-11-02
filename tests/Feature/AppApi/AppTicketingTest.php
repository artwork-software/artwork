<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** The door in the app: released dates, guest list, check-in and pass admission — through artwork to tickets. */
final class AppTicketingTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const DATE_ID = '7a2e3d4c-5b6f-4a71-9b82-0c3d4e5f6a71';
    private const TICKET_ID = '1b2c3d4e-5f60-4a71-8b92-0c1d2e3f4a5b';
    private const PASS_ID = '2c3d4e5f-6a71-4b82-9c03-1d2e3f4a5b6c';
    private const CATEGORY_ID = '3d4e5f6a-7b82-4c93-8d14-2e3f4a5b6c7d';
    private const ADMISSION_ID = '4e5f6a7b-8c93-4da4-9e25-3f4a5b6c7d8e';
    private const SCAN_ID = '4e5f6a7b-8c93-4da4-9e25-3f4a5b6c7d8e';
    private const API = self::TICKETS_URL . '/api/integration/v1';
    private const BASE = self::API . '/dates/' . self::DATE_ID;

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

        $this->admin = $this->adminUser();
        Passport::actingAs($this->admin, ['app']);

        $project = Project::factory()->create(['name' => 'Hamlet']);
        $type = EventType::factory()->create(['relevant_for_ticketing' => true]);
        $this->event = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $type->id,
            'room_id' => Room::factory()->create(['name' => 'Großes Haus'])->id,
            'start_time' => '2027-02-01 19:30:00',
            'end_time' => '2027-02-01 22:00:00',
        ]);
        TicketingEventRelease::query()->create([
            'event_id' => $this->event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);
    }

    #[Test]
    public function theEventEditorLearnsThatADateIsOnSale(): void
    {
        $show = route('app.v1.projects.events.show', [$this->event->project_id, $this->event->id]);

        $this->getJson($show)->assertOk()->assertJsonPath('ticketing_on_sale', true);

        TicketingConnection::query()->delete();

        $this->getJson($show)->assertOk()->assertJsonPath('ticketing_on_sale', false);
    }

    #[Test]
    public function movingADateOnSaleNeedsTheConfirmationHeader(): void
    {
        $update = route('app.v1.projects.events.update', [$this->event->project_id, $this->event->id]);
        $moved = [
            'name' => null,
            'start' => '2027-02-02T19:30:00+01:00',
            'end' => '2027-02-02T22:00:00+01:00',
            'all_day' => false,
            'room_id' => $this->event->room_id,
            'event_type_id' => $this->event->event_type_id,
        ];
        Http::fake();

        $this->patchJson($update, $moved)->assertUnprocessable();
        $this->patchJson($update, $moved, ['X-Ticketing-Move-Confirmed' => '1'])->assertOk();
    }

    #[Test]
    public function meSaysWhetherTheUserMayRunTheDoor(): void
    {
        $this->getJson(route('app.v1.me'))
            ->assertOk()
            ->assertJsonPath('user.permissions.can_run_ticket_admission', true);

        TicketingConnection::query()->delete();

        $this->getJson(route('app.v1.me'))
            ->assertOk()
            ->assertJsonPath('user.permissions.can_run_ticket_admission', false);
    }

    #[Test]
    public function theDatesCarryTheirCountersFromTickets(): void
    {
        Http::fake([
            self::API . '/dates?ids=*' => Http::response([
                ['id' => self::DATE_ID, 'status' => 'scheduled', 'capacity' => 300, 'sold' => 84, 'checkedInCount' => 12],
            ]),
        ]);

        $this->getJson(route('app.v1.ticketing.dates', ['start' => '2027-02-01', 'end' => '2027-02-07']))
            ->assertOk()
            ->assertJsonPath('dates.0.event_id', $this->event->id)
            ->assertJsonPath('dates.0.project.name', 'Hamlet')
            ->assertJsonPath('dates.0.room', 'Großes Haus')
            ->assertJsonPath('dates.0.sold', 84)
            ->assertJsonPath('dates.0.checked_in', 12)
            ->assertJsonPath('dates.0.cancelled', false);
    }

    #[Test]
    public function theDateBringsItsGuestList(): void
    {
        Http::fake([self::BASE => Http::response($this->sales())]);

        $this->getJson(route('app.v1.ticketing.dates.show', $this->event))
            ->assertOk()
            ->assertJsonPath('date.ticket_count', 1)
            ->assertJsonPath('date.doors_at', '2027-02-01T18:30:00.000Z')
            ->assertJsonPath('date.tickets.0.holder_name', 'Ada Lovelace')
            ->assertJsonPath('date.tickets.0.requires_proof', true)
            ->assertJsonMissingPath('date.revenue_cents');
    }

    #[Test]
    public function aDateOffSaleIsNotFound(): void
    {
        Http::fake();
        $this->event->ticketingRelease->update(['state' => 'withdrawn']);

        $this->getJson(route('app.v1.ticketing.dates.show', $this->event))->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function aScanGoesToTicketsWithItsScanId(): void
    {
        Http::fake([self::BASE . '/check-ins' => Http::response($this->checkIn())]);

        $this->postJson(route('app.v1.ticketing.check-ins', $this->event), [
            'scan_id' => self::SCAN_ID,
            'payload' => 'atk:' . self::TICKET_ID . ':secret',
        ])
            ->assertOk()
            ->assertJsonPath('outcome', 'accepted')
            ->assertJsonPath('ticket.holder_name', 'Ada Lovelace')
            ->assertJsonPath('ticket.checked_in_at', '2027-02-01T19:12:00.000Z');

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE . '/check-ins'
            && $request['scanId'] === self::SCAN_ID
            && $request['payload'] === 'atk:' . self::TICKET_ID . ':secret'
            && !isset($request['ticketId'])
            && $request['actor']['email'] === $this->admin->email);
    }

    #[Test]
    public function aCheckInNamesEitherTheScanOrTheTicket(): void
    {
        Http::fake();

        $this->postJson(route('app.v1.ticketing.check-ins', $this->event), [
            'scan_id' => self::SCAN_ID,
            'payload' => 'atk:x',
            'ticket_id' => self::TICKET_ID,
        ])->assertUnprocessable();
        $this->postJson(route('app.v1.ticketing.check-ins', $this->event), ['scan_id' => self::SCAN_ID])
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    #[Test]
    public function aScannedCardIsLookedUpAndAdmitted(): void
    {
        Http::fake([
            self::BASE . '/passes*' => Http::response([
                'seated' => false,
                'categories' => [['id' => self::CATEGORY_ID, 'name' => 'Parkett', 'priceCents' => 2900, 'available' => 40]],
                'passes' => [[
                    'id' => self::PASS_ID, 'code' => 'P-1234', 'name' => '10er-Karte', 'holderName' => 'Ada Lovelace',
                    'holderEmail' => 'ada@example.org', 'subscription' => false, 'usesLeft' => 4, 'validUntil' => null,
                    'block' => null, 'toPay' => [['priceCategoryId' => self::CATEGORY_ID, 'cents' => 0]],
                ]],
            ]),
            self::BASE . '/pass-admissions' => Http::response([
                'orderNumber' => 'ATK-2027-000001', 'ticketCode' => 'ABCD1234', 'paidCents' => 0, 'checkedInAt' => '2027-02-01T19:12:00.000Z',
            ]),
        ]);

        $this->getJson(route('app.v1.ticketing.passes', ['event' => $this->event, 'payload' => 'aps:' . self::PASS_ID . ':secret']))
            ->assertOk()
            ->assertJsonPath('passes.0.uses_left', 4)
            ->assertJsonPath('categories.0.price_cents', 2900);
        $this->postJson(route('app.v1.ticketing.pass-admissions', $this->event), [
            'admission_id' => self::ADMISSION_ID, 'pass_id' => self::PASS_ID, 'price_category_id' => self::CATEGORY_ID,
        ])
            ->assertOk()
            ->assertJsonPath('order_number', 'ATK-2027-000001');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), self::BASE . '/passes')
            && $request['payload'] === 'aps:' . self::PASS_ID . ':secret'
            && !isset($request['q']));
    }

    #[Test]
    public function aRefusalFromTicketsComesBackInWords(): void
    {
        Http::fake([
            self::BASE . '/pass-admissions' => Http::response(['error' => ['code' => 'PASS_EXHAUSTED', 'message' => 'PASS_EXHAUSTED']], 409),
        ]);

        $this->postJson(route('app.v1.ticketing.pass-admissions', $this->event), [
            'admission_id' => self::ADMISSION_ID, 'pass_id' => self::PASS_ID, 'price_category_id' => self::CATEGORY_ID,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('This pass has no uses left.'))
            ->assertJsonPath('code', 'PASS_EXHAUSTED');
    }

    #[Test]
    public function theDoorNeedsTheAdmissionPermission(): void
    {
        Http::fake();
        $this->actingAsApiUserWith([PermissionEnum::TICKETING_VIEW_SALES]);

        $this->getJson(route('app.v1.ticketing.dates'))->assertForbidden();
        $this->getJson(route('app.v1.ticketing.dates.show', $this->event))->assertForbidden();
        $this->postJson(route('app.v1.ticketing.check-ins', $this->event), [
            'scan_id' => self::SCAN_ID, 'ticket_id' => self::TICKET_ID,
        ])->assertForbidden();

        Http::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function sales(): array
    {
        return [
            'id' => self::DATE_ID, 'status' => 'scheduled', 'doorsAt' => '2027-02-01T18:30:00.000Z',
            'capacity' => 300, 'sold' => 1, 'ticketCount' => 1, 'checkedInCount' => 0, 'revenueCents' => 2900,
            'tickets' => [[
                'id' => self::TICKET_ID, 'code' => 'ABCD1234', 'status' => 'valid', 'holderName' => 'Ada Lovelace',
                'email' => 'ada@example.org', 'categoryName' => 'Parkett', 'reductionName' => 'Studierende',
                'priceCents' => 1500, 'requiresProof' => true,
                'seat' => ['section' => null, 'row' => null, 'number' => null], 'checkedInAt' => null,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function checkIn(): array
    {
        return [
            'scanId' => self::SCAN_ID,
            'outcome' => 'accepted',
            'ticket' => [
                'id' => self::TICKET_ID, 'code' => 'ABCD1234', 'holderName' => 'Ada Lovelace', 'categoryName' => 'Parkett',
                'requiresProof' => false, 'seat' => ['section' => null, 'row' => null, 'number' => null],
                'checkedInAt' => '2027-02-01T19:12:00.000Z',
            ],
        ];
    }
}
