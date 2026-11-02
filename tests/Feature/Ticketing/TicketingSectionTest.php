<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** Hauptmenü → Artwork-Tickets: Verkäufe sowie Karten und Abos, gelesen aus tickets. */
final class TicketingSectionTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const BASE = self::TICKETS_URL . '/api/integration/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));
    }

    private function connect(): void
    {
        TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => 1,
            'connected_by_user_id' => null,
        ]);
    }

    #[Test]
    public function the_sales_page_shows_the_period_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/sales*' => Http::response(['hasEverSold' => true, 'current' => ['revenueCents' => 5800], 'orders' => []])]);

        $this->get(route('ticketing.section.sales', ['days' => 7]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ticketing/Sales')
                ->where('connected', true)
                ->where('sales.current.revenueCents', 5800)
                ->where('filters.days', '7'));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/sales')
            && $request['days'] === '7');
    }

    #[Test]
    public function several_productions_and_a_custom_range_are_passed_on(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/sales*' => Http::response(['hasEverSold' => true, 'orders' => []])]);
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();

        $this->get(route('ticketing.section.sales', ['from' => '2026-09-01', 'to' => '2026-09-30', 'events' => [$first, $second]]))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/sales')
            && $request['from'] === '2026-09-01'
            && $request['to'] === '2026-09-30'
            && $request['events'] === "{$first},{$second}");
    }

    #[Test]
    public function a_range_needs_both_ends_in_order(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake();

        $this->get(route('ticketing.section.sales', ['from' => '2026-09-30', 'to' => '2026-09-01']))->assertSessionHasErrors('from');
        $this->get(route('ticketing.section.sales', ['from' => '2026-09-01']))->assertSessionHasErrors('to');
        Http::assertNothingSent();
    }

    #[Test]
    public function passes_and_subscriptions_are_one_list_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/holdings*' => Http::response(['holdings' => [
            ['kind' => 'pass', 'id' => 'p1', 'name' => '10er-Karte'],
            ['kind' => 'subscription', 'id' => 's1', 'planName' => 'Jahresabo'],
        ]])]);

        $this->get(route('ticketing.section.passes', ['q' => 'Ada']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ticketing/Passes')
                ->where('holdings.0.name', '10er-Karte')
                ->where('holdings.1.planName', 'Jahresabo'));
        $this->get(route('ticketing.section.passes', ['kind' => 'subscription', 'state' => 'pastDue']))->assertOk();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/holdings') && ($request->data()['q'] ?? null) === 'Ada');
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/holdings')
            && ($request->data()['kind'] ?? null) === 'subscription'
            && $request['state'] === 'pastDue');
    }

    #[Test]
    public function without_a_connection_the_page_says_so(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake();

        $this->get(route('ticketing.section.sales'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('connected', false)->where('sales', null));

        Http::assertNothingSent();
    }

    #[Test]
    public function the_passes_page_tells_who_may_manage(): void
    {
        $this->connect();
        Http::fake([self::BASE . '/holdings*' => Http::response(['holdings' => []])]);

        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        $this->get(route('ticketing.section.passes'))
            ->assertInertia(fn ($page) => $page->where('mayManage', false));

        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $this->get(route('ticketing.section.passes'))
            ->assertInertia(fn ($page) => $page->where('mayManage', true));
    }

    #[Test]
    public function a_pass_is_read_with_its_history(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        $passId = (string) Str::uuid();
        Http::fake([self::BASE . "/passes/{$passId}" => Http::response(['pass' => ['id' => $passId, 'history' => []]])]);

        $this->getJson(route('ticketing.section.pass', $passId))
            ->assertOk()
            ->assertJsonPath('pass.id', $passId);
    }

    #[Test]
    public function a_correction_goes_to_tickets_under_the_user(): void
    {
        $this->connect();
        $user = $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $passId = (string) Str::uuid();
        Http::fake([self::BASE . "/passes/{$passId}/commands" => Http::response(['pass' => ['id' => $passId, 'usesTotal' => 12]])]);

        $this->postJson(route('ticketing.section.pass.correct', $passId), ['kind' => 'adjust', 'delta' => 2, 'note' => 'Vorstellung ausgefallen'])
            ->assertOk()
            ->assertJsonPath('pass.usesTotal', 12);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE . "/passes/{$passId}/commands"
            && $request['command'] === ['kind' => 'adjust', 'delta' => 2, 'note' => 'Vorstellung ausgefallen']
            && $request['actor']['email'] === $user->email);
    }

    #[Test]
    public function the_last_day_of_a_subscription_pass_is_not_set_by_hand(): void
    {
        $this->connect();
        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $passId = (string) Str::uuid();
        Http::fake([self::BASE . "/passes/{$passId}" => Http::response(['pass' => ['id' => $passId, 'subscriptionId' => (string) Str::uuid()]])]);

        $this->postJson(route('ticketing.section.pass.correct', $passId), ['kind' => 'validUntil', 'day' => '2027-06-30'])
            ->assertJsonValidationErrors('day');
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/commands'));
    }

    #[Test]
    public function a_bare_code_from_tickets_never_reaches_the_dialog(): void
    {
        $this->connect();
        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $passId = (string) Str::uuid();
        Http::fake([self::BASE . "/passes/{$passId}/commands" => Http::sequence()
            ->push(['error' => ['code' => 'NOT_FOUND', 'message' => 'NOT_FOUND']], 404)
            ->push(['error' => ['code' => 'SOMETHING_NEW', 'message' => 'SOMETHING_NEW']], 409)]);

        $this->postJson(route('ticketing.section.pass.correct', $passId), ['kind' => 'revoke'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('Artwork-Tickets no longer has this entry. Refresh the view and try again.'));
        $this->postJson(route('ticketing.section.pass.correct', $passId), ['kind' => 'revoke'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('Artwork-Tickets rejected the request.') . ' (HTTP 409)');
    }

    #[Test]
    public function the_list_of_holdings_comes_as_json_for_the_dialog(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/holdings*' => Http::response(['holdings' => [['id' => 'a', 'kind' => 'subscription']]])]);

        $this->getJson(route('ticketing.section.holdings', ['kind' => 'subscription', 'q' => 'ada@example.org']))
            ->assertOk()
            ->assertJsonPath('holdings.0.id', 'a');
        Http::assertSent(fn (Request $request): bool => $request['kind'] === 'subscription' && $request['q'] === 'ada@example.org');
    }

    #[Test]
    public function a_correction_needs_a_count_other_than_zero(): void
    {
        $this->connect();
        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        Http::fake();

        $this->postJson(route('ticketing.section.pass.correct', (string) Str::uuid()), ['kind' => 'adjust', 'delta' => 0])
            ->assertJsonValidationErrors('delta');
        Http::assertNothingSent();
    }

    #[Test]
    public function a_pause_names_the_day_the_pass_works_again_and_who_paused(): void
    {
        $this->connect();
        $user = $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $subscriptionId = (string) Str::uuid();
        $day = now()->addWeeks(4)->format('Y-m-d');
        Http::fake([self::BASE . "/subscriptions/{$subscriptionId}/commands" => Http::response(['done' => true])]);

        $this->postJson(route('ticketing.section.subscription.change', $subscriptionId), ['kind' => 'pause', 'resumes_on' => $day])
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['command'] === ['kind' => 'pause', 'resumesOn' => $day]
            && $request['actor']['email'] === $user->email);
    }

    #[Test]
    public function a_pause_has_to_end_in_the_future(): void
    {
        $this->connect();
        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        Http::fake();

        $this->postJson(route('ticketing.section.subscription.change', (string) Str::uuid()), ['kind' => 'pause', 'resumes_on' => now()->format('Y-m-d')])
            ->assertJsonValidationErrors('resumes_on');
        Http::assertNothingSent();
    }

    #[Test]
    public function a_refusal_from_tickets_comes_back_in_words(): void
    {
        $this->connect();
        $this->actingAsUserWith([PermissionEnum::TICKETING_VIEW_SALES->value, PermissionEnum::TICKETING_MANAGE_PASSES->value]);
        $subscriptionId = (string) Str::uuid();
        Http::fake([self::BASE . "/subscriptions/{$subscriptionId}/commands" => Http::response(
            ['error' => ['code' => 'SUBSCRIPTION_NOT_PAUSABLE', 'message' => 'Not pausable']],
            409,
        )]);

        $this->postJson(route('ticketing.section.subscription.change', $subscriptionId), ['kind' => 'pause', 'resumes_on' => now()->addWeek()->format('Y-m-d')])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('This subscription cannot be paused right now: it is paused already, has ended or its last payment is open.'));
    }

    #[Test]
    public function changing_passes_needs_its_own_permission(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake();

        $this->postJson(route('ticketing.section.pass.correct', (string) Str::uuid()), ['kind' => 'revoke'])->assertForbidden();
        $this->postJson(route('ticketing.section.subscription.change', (string) Str::uuid()), ['kind' => 'endNow'])->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    public function the_section_needs_its_permission(): void
    {
        $this->connect();
        $this->actingAsUserWith([]);

        $this->get(route('ticketing.section.sales'))->assertForbidden();
        $this->get(route('ticketing.section.passes'))->assertForbidden();
    }
}
