<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/**
 * Tab "Angaben & Auszahlung": was der Assistent offen lassen durfte, wird hier nachgetragen — in tickets;
 * das Auszahlungskonto prüft Stripe in seinem Formular, dessen Sitzung tickets ausstellt.
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
    private static function ticketsAnswer(bool $legal, string $payoutAccount, bool $termsAccepted = true): array
    {
        return [
            'profile' => [
                'legalName' => 'Theater Süd gGmbH', 'legalForm' => 'ggmbh', 'street' => 'Theaterstraße 1', 'postalCode' => '20095',
                'city' => 'Hamburg', 'country' => 'DE', 'registerNumber' => null, 'registerCourt' => null, 'vatId' => 'DE123456789',
                'taxNumber' => null, 'contactName' => 'Erika Muster', 'contactPhone' => '+49 40 123456', 'website' => null,
                'termsUrl' => null, 'privacyUrl' => 'https://theater-sued.de/datenschutz', 'imprintUrl' => 'https://theater-sued.de/impressum',
            ],
            'legalComplete' => $legal,
            'shopLegalFiles' => [
                'terms' => ['fileName' => 'agb.pdf', 'url' => 'https://tickets.test/de/theater-sued/legal/terms'],
                'privacy' => null,
                'imprint' => null,
            ],
            'shopLegalComplete' => true,
            'payoutAccount' => $payoutAccount,
            'stripePublishableKey' => 'pk_test_tickets',
            'platformTerms' => [
                'accepted' => $termsAccepted,
                'termsUrl' => 'https://artwork-tickets.de/agb',
                'dpaUrl' => 'https://artwork-tickets.de/avv',
            ],
        ];
    }

    #[Test]
    public function the_tab_shows_the_profile_as_a_draft_with_both_states(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(self::ticketsAnswer(true, 'review'))]);

        $this->get(route('settings.tickets.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Billing')
                ->where('connection.connected', true)
                ->where('billing.profile.legal_name', 'Theater Süd gGmbH')
                ->where('billing.profile.register_number', '')
                ->where('billing.legal_complete', true)
                ->where('billing.payout_account', 'review')
                ->where('billing.stripe_key', 'pk_test_tickets')
                ->where('billing.profile.terms_url', '')
                ->where('billing.profile.imprint_url', 'https://theater-sued.de/impressum')
                ->where('billing.shop_legal_files.terms.file_name', 'agb.pdf')
                ->where('billing.shop_legal_files.privacy', null)
                ->where('billing.platform_terms.accepted', true)
                ->where('billing.platform_terms.dpa_url', 'https://artwork-tickets.de/avv')
                ->where('ticketsError', null));

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::TICKETS_URL . '/api/integration/v1/house/billing'
            && $request->header('x-api-key') === ['tk_plain']);
    }

    #[Test]
    public function the_details_count_as_complete_only_once_verified_and_signed(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fakeSequence(self::TICKETS_URL . '/api/integration/v1/house/billing')
            ->push(self::ticketsAnswer(true, 'review'))
            ->push(self::ticketsAnswer(true, 'verified', termsAccepted: false))
            ->push(self::ticketsAnswer(true, 'verified'));

        $this->get(route('settings.tickets'))->assertInertia(fn ($page) => $page->where('connection.billingComplete', false));
        $this->get(route('settings.tickets'))->assertInertia(fn ($page) => $page->where('connection.billingComplete', false));
        $this->get(route('settings.tickets'))->assertInertia(fn ($page) => $page->where('connection.billingComplete', true));
    }

    #[Test]
    public function new_terms_are_accepted_by_the_user_who_ticks_the_box(): void
    {
        $this->connect();
        $user = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/platform-terms' => Http::response(self::ticketsAnswer(true, 'verified'))]);

        $this->from(route('settings.tickets.billing'))
            ->post(route('settings.tickets.billing.platform-terms'))
            ->assertRedirect(route('settings.tickets.billing'))
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'POST'
            && $request->header('x-api-key') === ['tk_plain']
            && $request['acceptedBy'] === ['email' => $user->email, 'name' => $user->full_name]);
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
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(self::ticketsAnswer(true, 'open'))]);

        $this->from(route('settings.tickets.billing'))
            ->post(route('settings.tickets.billing.save'), [
                'legal_name' => 'Theater Süd gGmbH', 'legal_form' => 'ggmbh', 'street' => 'Theaterstraße 1', 'postal_code' => '20095',
                'city' => 'Hamburg', 'country' => 'DE', 'register_number' => '', 'register_court' => '', 'vat_id' => 'DE123456789',
                'tax_number' => '', 'contact_name' => 'Erika Muster', 'contact_phone' => '+49 40 123456', 'website' => '',
                'terms_url' => '', 'privacy_url' => 'https://theater-sued.de/datenschutz', 'imprint_url' => 'https://theater-sued.de/impressum',
            ])
            ->assertRedirect(route('settings.tickets.billing'))
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'PUT'
            && $request['legalName'] === 'Theater Süd gGmbH'
            && $request['registerNumber'] === ''
            && $request['taxNumber'] === ''
            && $request['website'] === ''
            && $request['termsUrl'] === ''
            && $request['imprintUrl'] === 'https://theater-sued.de/impressum');
    }

    #[Test]
    public function a_picked_pdf_goes_to_tickets_before_the_fields(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/*' => Http::response(self::ticketsAnswer(true, 'open'))]);

        $draft = array_fill_keys(['legal_name', 'legal_form', 'street', 'postal_code', 'city', 'register_number', 'register_court',
            'vat_id', 'tax_number', 'contact_name', 'contact_phone', 'website', 'terms_url', 'privacy_url', 'imprint_url'], '');

        $this->from(route('settings.tickets.billing'))
            ->post(route('settings.tickets.billing.save'), $draft + [
                'country' => 'DE',
                'terms_file' => UploadedFile::fake()->create('agb.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $sent = Http::recorded()->map(fn (array $pair): string => $pair[0]->method() . ' ' . $pair[0]->url())->all();
        $this->assertSame([
            'PUT ' . self::TICKETS_URL . '/api/integration/v1/house/legal-documents/terms',
            'PUT ' . self::TICKETS_URL . '/api/integration/v1/house/billing',
        ], $sent);
        Http::assertSent(static fn (Request $request): bool => str_ends_with($request->url(), '/legal-documents/terms')
            && $request->header('x-file-name') === ['agb.pdf']
            && $request->header('Content-Type') === ['application/pdf']);
    }

    #[Test]
    public function a_stored_pdf_can_be_removed_and_only_a_pdf_is_accepted(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/*' => Http::response(self::ticketsAnswer(true, 'open'))]);

        $this->from(route('settings.tickets.billing'))
            ->delete(route('settings.tickets.billing.documents.remove', 'privacy'))
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/house/legal-documents/privacy'));

        $this->delete('/settings/tickets/billing/documents/contract')->assertNotFound();

        $this->from(route('settings.tickets.billing'))
            ->post(route('settings.tickets.billing.save'), ['country' => 'DE', 'imprint_file' => UploadedFile::fake()->image('impressum.png')])
            ->assertSessionHasErrors(['imprint_file']);
    }

    #[Test]
    public function stripes_form_gets_its_session_from_tickets_with_the_user_as_contact(): void
    {
        $this->connect();
        $user = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/stripe-session' => Http::response(['clientSecret' => 'accs_secret'])]);

        $this->postJson(route('settings.tickets.billing.stripe-session'))
            ->assertOk()
            ->assertExactJson(['clientSecret' => 'accs_secret']);

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'POST'
            && $request->header('x-api-key') === ['tk_plain']
            && $request['email'] === $user->email);
    }

    #[Test]
    public function without_tickets_stripes_form_gets_no_session(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::TICKETS_URL . '/api/integration/v1/house/stripe-session' => Http::response(null, 503)]);

        $this->postJson(route('settings.tickets.billing.stripe-session'))->assertStatus(409);
        Http::assertNothingSent();

        $this->connect();

        $this->postJson(route('settings.tickets.billing.stripe-session'))->assertStatus(502);
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
            'vat_id', 'tax_number', 'contact_name', 'contact_phone', 'website', 'terms_url', 'privacy_url', 'imprint_url'], '');

        $this->post(route('settings.tickets.billing.save'), $empty + ['country' => 'DE'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }
}
