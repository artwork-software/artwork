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
 * Tab "Angaben & Bankverbindung": was der Assistent offen lassen durfte, wird hier nachgetragen — in tickets.
 */
final class TicketingBillingTest extends FeatureTestCase
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

    /** @return array<string, mixed> */
    private static function ticketsAnswer(bool $legal, bool $bank): array
    {
        return [
            'profile' => [
                'legalName' => 'Theater Süd gGmbH', 'legalForm' => 'ggmbh', 'street' => 'Theaterstraße 1', 'postalCode' => '20095',
                'city' => 'Hamburg', 'country' => 'DE', 'registerNumber' => null, 'registerCourt' => null, 'vatId' => 'DE123456789',
                'taxNumber' => null, 'contactName' => 'Erika Muster', 'contactPhone' => '+49 40 123456', 'website' => null,
                'accountHolder' => $bank ? 'Theater Süd gGmbH' : null, 'ibanLast4' => $bank ? '3000' : null,
            ],
            'legalComplete' => $legal,
            'bankComplete' => $bank,
        ];
    }

    #[Test]
    public function the_tab_shows_the_profile_as_a_draft_with_both_states(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(self::ticketsAnswer(true, false))]);

        $this->get(route('settings.tickets.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Billing')
                ->where('connection.connected', true)
                ->where('billing.profile.legal_name', 'Theater Süd gGmbH')
                ->where('billing.profile.register_number', '')
                ->where('billing.profile.iban', '')
                ->where('billing.iban_last4', null)
                ->where('billing.legal_complete', true)
                ->where('billing.bank_complete', false)
                ->where('ticketsError', null));

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::TICKETS_URL . '/api/integration/v1/house/billing'
            && $request->header('x-api-key') === ['tk_plain']);
    }

    #[Test]
    public function the_tab_still_renders_when_tickets_is_unreachable(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(null, 503)]);

        $this->get(route('settings.tickets.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('billing', null)
                ->where('ticketsError', fn (string $message): bool => $message !== ''));
    }

    #[Test]
    public function saving_sends_the_draft_as_tickets_expects_it(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(self::ticketsAnswer(true, true))]);

        $this->from(route('settings.tickets.billing'))
            ->put(route('settings.tickets.billing.save'), [
                'legal_name' => 'Theater Süd gGmbH', 'legal_form' => 'ggmbh', 'street' => 'Theaterstraße 1', 'postal_code' => '20095',
                'city' => 'Hamburg', 'country' => 'DE', 'register_number' => '', 'register_court' => '', 'vat_id' => 'DE123456789',
                'tax_number' => '', 'contact_name' => 'Erika Muster', 'contact_phone' => '+49 40 123456', 'website' => '',
                'account_holder' => 'Theater Süd gGmbH', 'iban' => 'DE89 3704 0044 0532 0130 00',
            ])
            ->assertRedirect(route('settings.tickets.billing'))
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'PUT'
            && $request['legalName'] === 'Theater Süd gGmbH'
            && $request['registerNumber'] === ''
            && $request['taxNumber'] === ''
            && $request['website'] === ''
            && $request['iban'] === 'DE89370400440532013000');
    }

    #[Test]
    public function an_empty_iban_keeps_the_stored_one_and_a_wrong_one_is_refused(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(self::ticketsAnswer(false, true))]);

        $draft = ['legal_name' => '', 'legal_form' => '', 'street' => '', 'postal_code' => '', 'city' => '', 'country' => 'DE',
            'register_number' => '', 'register_court' => '', 'vat_id' => '', 'tax_number' => '', 'contact_name' => '',
            'contact_phone' => '', 'website' => '', 'account_holder' => 'Theater Süd gGmbH'];

        $this->from(route('settings.tickets.billing'))
            ->put(route('settings.tickets.billing.save'), $draft + ['iban' => 'DE88 3704 0044 0532 0130 00'])
            ->assertSessionHasErrors(['iban']);

        Http::assertNothingSent();

        $this->put(route('settings.tickets.billing.save'), $draft + ['iban' => ''])
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => $request['iban'] === '' && $request['legalName'] === '');
    }

    #[Test]
    public function without_a_connection_the_tab_offers_to_connect_and_saving_is_refused(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->get(route('settings.tickets.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('connection.connected', false)->where('billing', null));

        $empty = array_fill_keys(['legal_name', 'legal_form', 'street', 'postal_code', 'city', 'register_number', 'register_court',
            'vat_id', 'tax_number', 'contact_name', 'contact_phone', 'website', 'account_holder', 'iban'], '');

        $this->put(route('settings.tickets.billing.save'), $empty + ['country' => 'DE'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }
}
