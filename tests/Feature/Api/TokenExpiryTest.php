<?php

namespace Tests\Feature\Api;

use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-schritt1-rueckfluss, Rueckfluss 2 aus Konzept 0.1 §4.
 *
 * Die Token-Verwaltung schreibt ein Ablaufdatum in oauth_access_tokens.expires_at, Passport liest
 * die Spalte beim Authentifizieren aber nie — das JWT traegt die globale PAT-Laufzeit von einem
 * Jahr. Durchgesetzt wird das Ablaufdatum vom ExtendedTokenValidator schon in auth:api; die Antwort
 * ist deshalb das allgemeine 401 des Guards.
 */
final class TokenExpiryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    private function tokenFor(User $user, ?string $expiresAt): string
    {
        $result = $user->createToken('kunden-key', ['inventory:read']);

        if ($expiresAt !== null) {
            Passport::token()->newQuery()
                ->whereKey($result->token->id)
                ->update(['expires_at' => $expiresAt]);
        }

        return $result->accessToken;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function machineRoutes(): array
    {
        return [
            '/api/v1/inventory' => ['/api/v1/inventory'],
            '/api/inventory' => ['/api/inventory'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('machineRoutes')]
    public function an_expired_database_expiry_rejects_the_token(string $url): void
    {
        $token = $this->tokenFor(User::factory()->create(), now()->subDay()->toDateTimeString());

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson($url)
            ->assertUnauthorized();
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('machineRoutes')]
    public function a_token_without_an_expiry_still_works(string $url): void
    {
        $token = $this->tokenFor(User::factory()->create(), null);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson($url)
            ->assertOk();
    }

    #[Test]
    public function a_future_expiry_still_works(): void
    {
        $token = $this->tokenFor(User::factory()->create(), now()->addDay()->toDateTimeString());

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/inventory')
            ->assertOk();
    }

    /** Das JWT selbst bleibt gueltig — abgelehnt wird allein wegen der DB-Spalte. */
    #[Test]
    public function the_jwt_itself_is_still_valid_when_the_database_expiry_has_passed(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user, now()->subDay()->toDateTimeString());

        $claims = json_decode(
            base64_decode(strtr(explode('.', $token)[1], '-_', '+/')),
            true
        );

        $this->assertGreaterThan(time(), $claims['exp'], 'Das JWT-exp liegt weiterhin in der Zukunft');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/inventory')
            ->assertUnauthorized();
    }
}
