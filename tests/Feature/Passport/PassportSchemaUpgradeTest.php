<?php

namespace Tests\Feature\Passport;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sichert das Ergebnis von core-passport-schema-upgrade ab.
 *
 * Die Datennormalisierung selbst (client_credentials entfernen, Secret nullen) passiert in der
 * Migration und ist im Log mit dem Vorher/Nachher-Lauf gegen die Alt-Schema-DB belegt. Hier steht,
 * was dauerhaft gelten muss: das Schema, und dass ein Personal-Access-Token auch fuer den User
 * funktioniert, dessen ID mit der Client-ID kollidiert (TokenGuard.php:146).
 */
final class PassportSchemaUpgradeTest extends TestCase
{
    private function personalAccessClient(): Client
    {
        $repository = app(ClientRepository::class);
        $provider = config('auth.guards.api.provider');

        try {
            return $repository->personalAccessClient($provider);
        } catch (\RuntimeException) {
            return $repository->createPersonalAccessGrantClient('Test Personal Access Client', $provider);
        }
    }

    #[Test]
    public function the_clients_table_uses_the_passport_13_schema(): void
    {
        foreach (['owner_type', 'owner_id', 'redirect_uris', 'grant_types'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('oauth_clients', $column),
                "Spalte {$column} fehlt in oauth_clients"
            );
        }

        foreach (['user_id', 'redirect', 'personal_access_client', 'password_client'] as $column) {
            $this->assertFalse(
                Schema::hasColumn('oauth_clients', $column),
                "Alt-Spalte {$column} ist noch in oauth_clients"
            );
        }
    }

    #[Test]
    public function the_redundant_personal_access_client_table_is_gone(): void
    {
        $this->assertFalse(Schema::hasTable('oauth_personal_access_clients'));
    }

    #[Test]
    public function the_personal_access_client_is_findable_by_its_stored_grant_type(): void
    {
        $client = $this->personalAccessClient();

        $this->assertTrue($client->hasGrantType('personal_access'));
    }

    /**
     * Auf dem Alt-Schema leitete Client::grantTypes() client_credentials aus "confidential und
     * first-party" ab und traf damit jeden Personal-Access-Client. Auf dem 13er-Schema steht die
     * Liste in der Spalte und enthaelt nur, was gewollt ist.
     */
    #[Test]
    public function the_personal_access_client_can_not_issue_client_credentials_tokens(): void
    {
        $this->assertFalse($this->personalAccessClient()->hasGrantType('client_credentials'));
    }

    #[Test]
    public function a_personal_access_token_authenticates_against_the_machine_api(): void
    {
        $this->personalAccessClient();

        $token = User::factory()->create()->createToken('schema-upgrade-test', ['inventory:read']);

        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken)
            ->getJson('/api/v1/inventory')
            ->assertOk();
    }

    /**
     * Der Nebenbefund aus core-passport-verifikation: Ein User, dessen ID der Client-ID des
     * ausstellenden Clients entspricht, bekam vorher 401 ohne Meldung.
     */
    #[Test]
    public function a_user_whose_id_matches_the_client_id_can_use_a_personal_access_token(): void
    {
        $user = User::factory()->create();
        $client = $this->personalAccessClient();

        // Die Kollision herstellen: Client-ID auf die User-ID ziehen.
        Client::query()->whereKey($client->getKey())->update(['id' => $user->id]);

        $token = $user->createToken('schema-upgrade-collision', ['inventory:read']);

        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken)
            ->getJson('/api/v1/inventory')
            ->assertOk();
    }

    #[Test]
    public function the_api_management_screen_still_creates_tokens(): void
    {
        $this->personalAccessClient();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $before = Passport::token()->newQuery()->count();

        $this->post(route('api-management.store'), [
            'name' => 'schema-upgrade-api-management',
            'scopes' => ['inventory:read'],
        ])->assertRedirect();

        $this->assertSame($before + 1, Passport::token()->newQuery()->count());
    }
}
