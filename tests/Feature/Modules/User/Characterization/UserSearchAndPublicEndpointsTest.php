<?php

namespace Tests\Feature\Modules\User\Characterization;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserUserManagementSetting;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Such- und Listen-Endpunkte im User-Modul (users.search, user.scoutSearch,
 * users.money_source_search, users.addresses) sowie der öffentlichen Routen Passwort-Reset-Seite
 * und Buchstaben-Avatar. Scout läuft in Tests mit dem null-Treiber, Scout-Suchen liefern daher leer.
 */
final class UserSearchAndPublicEndpointsTest extends FeatureTestCase
{
    #[Test]
    public function user_search_matches_first_or_last_name_prefix(): void
    {
        $byFirstName = User::factory()->create(['first_name' => 'Zxqfirst', 'last_name' => 'Muster']);
        $byLastName = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Zxqlast']);
        User::factory()->create(['first_name' => 'Anna', 'last_name' => 'AZxq']);
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('users.search', ['query' => 'Zxq']))->assertOk();

        $this->assertEqualsCanonicalizing(
            [$byFirstName->id, $byLastName->id],
            array_column($response->json(), 'id')
        );
        $this->assertSame('UserIndexResource', $response->json('0.resource'));
    }

    #[Test]
    public function user_search_hides_private_contact_data_from_regular_users(): void
    {
        User::factory()->create([
            'first_name' => 'Zxqprivat',
            'email_private' => true,
            'phone_private' => true,
            'phone_number' => '0123',
        ]);
        $this->actingAs(User::factory()->create());

        $this->getJson(route('users.search', ['query' => 'Zxqprivat']))
            ->assertOk()
            ->assertJsonPath('0.email', null)
            ->assertJsonPath('0.phone_number', null);
    }

    #[Test]
    public function user_search_requires_a_query(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('users.search'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    #[Test]
    public function user_search_requires_authentication(): void
    {
        $this->getJson(route('users.search', ['query' => 'a']))->assertUnauthorized();
    }

    #[Test]
    public function scout_search_returns_an_empty_list_without_search_term(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('user.scoutSearch'), [])->assertOk()->assertExactJson([]);
        $this->postJson(route('user.scoutSearch'), ['user_search' => ''])->assertOk()->assertExactJson([]);
    }

    #[Test]
    public function scout_search_with_term_answers_with_a_json_list(): void
    {
        User::factory()->create(['first_name' => 'Zxqscout']);
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('user.scoutSearch'), ['user_search' => 'Zxqscout'])->assertOk();

        $this->assertIsArray($response->json());
    }

    #[Test]
    public function money_source_search_requires_a_query_and_answers_with_a_json_list(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('users.money_source_search'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');

        $response = $this->getJson(route('users.money_source_search', ['query' => 'anything']))->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function addresses_page_lists_freelancers_and_service_providers_filtered_by_query(): void
    {
        $freelancer = Freelancer::factory()->create(['first_name' => 'Zxqfree', 'last_name' => 'Lancer']);
        Freelancer::factory()->create(['first_name' => 'Other', 'last_name' => 'Person']);
        $provider = ServiceProvider::factory()->create(['provider_name' => 'Zxq Licht GmbH']);
        ServiceProvider::factory()->create(['provider_name' => 'Ton AG']);
        $this->actingAsUserWith(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);

        $this->get(route('users.addresses', ['query' => 'Zxq']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Addresses')
                ->where('freelancers', fn ($items) => collect($items)->pluck('id')->all() === [$freelancer->id])
                ->where('serviceProviders', fn ($items) => collect($items)->pluck('id')->all() === [$provider->id])
                ->has('memberSortEnums', 4)
                ->has('userUserManagementSetting')
                ->has('catalog'));
    }

    #[Test]
    public function addresses_page_sorts_and_persists_the_sort_when_requested(): void
    {
        $bravo = Freelancer::factory()->create(['first_name' => 'Zxq', 'last_name' => 'Bravo']);
        $alpha = Freelancer::factory()->create(['first_name' => 'Zxq', 'last_name' => 'Alpha']);
        $user = $this->actingAsUserWith(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);

        $this->get(route('users.addresses', [
            'query' => 'Zxq',
            'sort' => 'ALPHABETICALLY_ASCENDING',
            'saveFilterAndSort' => 1,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('freelancers', fn ($items) => collect($items)->pluck('id')->all() === [$alpha->id, $bravo->id])
                ->where('userUserManagementSetting.sort_by', 'ALPHABETICALLY_ASCENDING'));

        $this->assertSame(
            'ALPHABETICALLY_ASCENDING',
            UserUserManagementSetting::query()->where('user_id', $user->id)->sole()->settings['sort_by']
        );
    }

    #[Test]
    public function addresses_page_applies_the_persisted_sort_on_later_visits(): void
    {
        $alpha = Freelancer::factory()->create(['first_name' => 'Qyx', 'last_name' => 'Alpha']);
        $bravo = Freelancer::factory()->create(['first_name' => 'Qyx', 'last_name' => 'Bravo']);
        $user = $this->actingAsUserWith(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);
        $this->get(route('users.addresses', ['sort' => 'ALPHABETICALLY_DESCENDING', 'saveFilterAndSort' => 1]))
            ->assertOk();

        $this->get(route('users.addresses', ['query' => 'Qyx']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('freelancers', fn ($items) => collect($items)->pluck('id')->all() === [$bravo->id, $alpha->id]));
    }

    #[Test]
    public function addresses_page_rejects_unknown_sort_values(): void
    {
        $this->actingAsUserWith(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);

        $this->getJson(route('users.addresses', ['sort' => 'RANDOM']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
    }

    #[Test]
    public function addresses_page_needs_the_same_rights_as_the_profile_pages(): void
    {
        // vorher: jede eingeloggte Person sah Kontaktdaten aller Freelancer/Dienstleister
        $this->actingAs(User::factory()->create());
        $this->get(route('users.addresses'))->assertForbidden();

        foreach ([
            PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO,
            PermissionEnum::EXTERNAL_MANAGER,
            PermissionEnum::SHIFT_PLANNER,
        ] as $permission) {
            $this->actingAsUserWith($permission->value);
            $this->get(route('users.addresses'))->assertOk();
        }
    }

    #[Test]
    public function addresses_page_redirects_guests_to_login(): void
    {
        $this->get(route('users.addresses'))->assertRedirect(route('login'));
    }

    #[Test]
    public function reset_password_page_is_public_and_passes_token_and_email(): void
    {
        $this->get(route('reset_user_password', ['token' => 'abc123', 'email' => 'person@example.test']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'abc123')
                ->where('email', 'person@example.test'));
    }

    #[Test]
    public function avatar_image_is_a_cacheable_svg_with_at_most_two_uppercase_letters(): void
    {
        $response = $this->get(route('generate-avatar-image', ['letters' => 'abc']))->assertOk();

        $this->assertStringStartsWith('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('aria-label="Avatar AB"', $response->getContent());
        $this->assertStringContainsString('fill="#00a3ff"', $response->getContent());
        $this->assertStringContainsString('fill="#ffffff"', $response->getContent());
    }

    #[Test]
    public function avatar_image_accepts_hex_colors_only(): void
    {
        $valid = $this->get(route('generate-avatar-image', ['letters' => 'JM', 'bg' => '#123abc', 'color' => '#fff']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('fill="#123abc"', $valid);
        $this->assertStringContainsString('fill="#fff"', $valid);

        $invalid = $this->get(route('generate-avatar-image', [
            'letters' => 'JM',
            'bg' => 'red" onload="alert(1)',
            'color' => ['#000'],
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('fill="#00a3ff"', $invalid);
        $this->assertStringContainsString('fill="#ffffff"', $invalid);
        $this->assertStringNotContainsString('onload', $invalid);
    }

    #[Test]
    public function avatar_image_escapes_the_letters(): void
    {
        $content = $this->get('/generate-avatar-image/' . rawurlencode('<s'))->assertOk()->getContent();

        $this->assertStringContainsString('&lt;S', $content);
        $this->assertStringNotContainsString('<S', $content);
    }
}
