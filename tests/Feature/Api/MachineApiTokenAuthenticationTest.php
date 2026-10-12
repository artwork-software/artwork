<?php

namespace Tests\Feature\Api;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Maschinen-Tokens über den echten auth:api-Guard — mit ausgestellten JWTs statt Passport::actingAs(),
 * weil beide Fehler erst bei der Validierung echter Tokens auftraten.
 */
final class MachineApiTokenAuthenticationTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nur erzeugen, wenn sie fehlen — --force würde lokal vorhandene Tokens entwerten.
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }
    }

    #[Test]
    public function token_of_user_whose_id_equals_the_personal_access_client_id_is_accepted(): void
    {
        $user = User::factory()->create();

        // Zustand der Instanzen nach der Migration: Legacy-Schema, Personal Access Client mit fester
        // Grant-Type-Liste. Ohne sie leitete Passport client_credentials ab, und der TokenGuard verwarf
        // jeden Token, dessen User-ID der Client-ID entspricht (typisch: User 1 / Client 1).
        DB::table('oauth_clients')->insert([
            'id' => $user->id,
            'user_id' => null,
            'name' => 'Artwork Personal Access Client',
            'secret' => 'secret',
            'provider' => null,
            'redirect' => 'http://localhost',
            'grant_types' => json_encode(['personal_access']),
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $user->createToken('Terpsi', ['inventory:read']);

        $this->assertSame((string) $user->id, (string) $result->token->client_id);

        $this->requestWith($result->accessToken)->assertOk();
    }

    #[Test]
    public function new_personal_access_client_gets_no_client_credentials_grant(): void
    {
        $client = app(ClientRepository::class)->createPersonalAccessGrantClient('Personal Access Client', 'users');

        $this->assertSame(['personal_access'], $client->fresh()->grant_types);
        $this->assertFalse($client->fresh()->hasGrantType('client_credentials'));
    }

    #[Test]
    public function token_is_rejected_after_the_expiry_date_set_in_the_settings(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Personal Access Client', 'users');
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $expiryDate = now()->addDay()->toDateString();

        $plainTextToken = $this
            ->post(route('api-management.store'), [
                'name' => 'Ticketshop',
                'scopes' => ['inventory:read'],
                'expires_at' => $expiryDate,
            ])
            ->assertSessionHasNoErrors()
            ->getSession()
            ->get('plainTextToken');

        // Ein reines Datum gilt bis Tagesende.
        $this->assertDatabaseHas('oauth_access_tokens', [
            'name' => 'Ticketshop',
            'expires_at' => "{$expiryDate} 23:59:59",
        ]);

        $this->requestWith($plainTextToken)->assertOk();

        // Der exp-Claim (Passport-Standard: ein Jahr) ist weiterhin gültig — league/oauth2-server prüft
        // ihn gegen die Systemuhr. Abgewiesen wird allein über expires_at.
        $this->travelTo(now()->addDay()->endOfDay()->addSecond());

        $this->requestWith($plainTextToken)->assertUnauthorized();
    }

    #[Test]
    public function token_without_expiry_date_stays_valid(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Personal Access Client', 'users');
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $plainTextToken = $this
            ->post(route('api-management.store'), ['name' => 'Ticketshop', 'scopes' => ['inventory:read']])
            ->getSession()
            ->get('plainTextToken');

        $this->travelTo(now()->addMonths(6));

        $this->requestWith($plainTextToken)->assertOk();
    }

    private function requestWith(string $token): TestResponse
    {
        // Web-Login aus actingAs() und zuvor authentifizierte Guards verwerfen: Geprüft wird nur der Bearer-Token.
        Auth::forgetGuards();

        return $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/inventory');
    }
}
