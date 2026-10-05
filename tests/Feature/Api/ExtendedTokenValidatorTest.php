<?php

namespace Tests\Feature\Api;

use Artwork\Modules\User\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Bewusste Ausnahme: per jti gelistete Tokens gelten bis zu einer Deadline über exp hinaus.
 *
 * Läuft über den echten auth:api-Guard. Tokens werden direkt als JWT gebaut, weil
 * league/oauth2-server eine SystemClock nutzt — Carbon-Zeitreisen wirken auf die
 * JWT-Validierung nicht. Abgelaufene Tokens entstehen daher über exp in der Vergangenheit.
 */
final class ExtendedTokenValidatorTest extends FeatureTestCase
{
    private const ROUTE = '/_test/extended-token';

    private Client $client;

    private User $user;

    /** @var array<string, string> */
    private array $jtis = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Nur erzeugen, wenn sie fehlen — --force würde lokal vorhandene Tokens entwerten.
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        $this->client = app(ClientRepository::class)->createPersonalAccessGrantClient('Test Client', 'users');
        $this->user = User::factory()->create();

        Route::middleware('auth:api')->get(self::ROUTE, static fn () => 'ok');

        $this->extend([]);
    }

    #[Test]
    public function valid_unlisted_token_is_accepted(): void
    {
        $this->requestWith($this->issueToken('+1 hour'))->assertOk();
    }

    #[Test]
    public function expired_unlisted_token_is_rejected(): void
    {
        $this->requestWith($this->issueToken('-1 day'))->assertUnauthorized();
    }

    #[Test]
    public function expired_listed_token_is_accepted_until_deadline(): void
    {
        $this->extend([$this->jti('listed') => $this->date('+30 days')]);

        $this->requestWith($this->issueToken('-1 day', $this->jti('listed')))->assertOk();
    }

    #[Test]
    public function expired_listed_token_is_rejected_after_deadline(): void
    {
        // Deadline liegt nach exp, aber schon in der Vergangenheit.
        $this->extend([$this->jti('listed') => $this->date('-1 day')]);

        $this->requestWith($this->issueToken('-10 days', $this->jti('listed')))->assertUnauthorized();
    }

    #[Test]
    public function revoked_listed_token_is_rejected(): void
    {
        $this->extend([$this->jti('listed') => $this->date('+30 days')]);

        $this->requestWith($this->issueToken('-1 day', $this->jti('listed'), revoked: true))
            ->assertUnauthorized();
    }

    #[Test]
    public function listed_token_signed_with_foreign_key_is_rejected(): void
    {
        $this->extend([$this->jti('listed') => $this->date('+30 days')]);

        $foreignKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($foreignKey, $foreignPem);

        $this->requestWith($this->issueToken('-1 day', $this->jti('listed'), signingKey: $foreignPem))
            ->assertUnauthorized();
    }

    #[Test]
    public function listed_token_with_tampered_exp_is_rejected(): void
    {
        $this->extend([$this->jti('listed') => $this->date('+30 days')]);

        $token = $this->issueToken('-1 day', $this->jti('listed'));
        [$header, $payload, $signature] = explode('.', $token);

        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['exp'] = (new DateTimeImmutable('+1 hour'))->getTimestamp();
        $tampered = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');

        $this->requestWith("{$header}.{$tampered}.{$signature}")->assertUnauthorized();
    }

    #[Test]
    public function listed_token_with_deadline_before_exp_uses_standard_path(): void
    {
        $this->extend([$this->jti('listed') => $this->date('-1 day')]);

        $this->requestWith($this->issueToken('+1 hour', $this->jti('listed')))->assertOk();
    }

    #[Test]
    public function listed_token_with_unparseable_deadline_behaves_like_unlisted(): void
    {
        $this->extend([
            $this->jti('expired') => 'kein-datum',
            $this->jti('valid') => 'kein-datum',
        ]);

        $this->requestWith($this->issueToken('-1 day', $this->jti('expired')))->assertUnauthorized();
        $this->requestWith($this->issueToken('+1 hour', $this->jti('valid')))->assertOk();
    }

    #[Test]
    public function empty_or_invalid_env_falls_back_to_standard_behaviour(): void
    {
        foreach (['', 'kein-json{', '"string"', '123'] as $envValue) {
            $config = $this->loadPassportConfigWithEnv($envValue);

            $this->assertSame([], $config['extended_tokens'], "Env-Wert: {$envValue}");

            $this->extend($config['extended_tokens']);

            $this->requestWith($this->issueToken('+1 hour'))->assertOk();
            $this->requestWith($this->issueToken('-1 day'))->assertUnauthorized();
        }
    }

    /**
     * Setzt die Ausnahmeliste und verwirft ResourceServer-Singleton und Guards, damit die
     * Konfiguration beim nächsten Request neu gelesen wird.
     *
     * @param array<string, string> $tokens
     */
    private function extend(array $tokens): void
    {
        config(['passport.extended_tokens' => $tokens]);

        app()->forgetInstance(ResourceServer::class);
        Auth::forgetGuards();
    }

    private function requestWith(string $token): TestResponse
    {
        // Guards zwischen Requests verwerfen, sonst bleibt der zuerst authentifizierte User hängen.
        Auth::forgetGuards();

        return $this->withHeader('Authorization', "Bearer {$token}")->getJson(self::ROUTE);
    }

    private function issueToken(
        string $expiresIn,
        ?string $jti = null,
        bool $revoked = false,
        ?string $signingKey = null,
    ): string {
        $jti ??= Str::random(40);
        $expiresAt = new DateTimeImmutable($expiresIn);
        $issuedAt = min(new DateTimeImmutable(), $expiresAt->modify('-1 hour'));

        DB::table('oauth_access_tokens')->insert([
            'id' => $jti,
            'user_id' => $this->user->id,
            'client_id' => $this->client->getKey(),
            'name' => 'Test Token',
            'scopes' => '[]',
            'revoked' => $revoked,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $key = $signingKey !== null
            ? InMemory::plainText($signingKey)
            : InMemory::file(Passport::keyPath('oauth-private.key'));

        return (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->permittedFor((string) $this->client->getKey())
            ->identifiedBy($jti)
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt)
            ->expiresAt($expiresAt)
            ->relatedTo((string) $this->user->id)
            ->withClaim('scopes', [])
            ->getToken(new Sha256(), $key)
            ->toString();
    }

    /** Stabile, zufällige jti pro Rolle innerhalb eines Tests — Konfiguration und Token müssen übereinstimmen. */
    private function jti(string $role): string
    {
        return $this->jtis[$role] ??= $role . '-' . Str::random(40);
    }

    private function date(string $relative): string
    {
        return (new DateTimeImmutable($relative))->format('Y-m-d H:i:s');
    }

    /** @return array<string, mixed> */
    private function loadPassportConfigWithEnv(string $value): array
    {
        $previous = getenv('PASSPORT_EXTENDED_TOKENS');

        putenv("PASSPORT_EXTENDED_TOKENS={$value}");
        $_ENV['PASSPORT_EXTENDED_TOKENS'] = $value;
        $_SERVER['PASSPORT_EXTENDED_TOKENS'] = $value;

        try {
            return require config_path('passport.php');
        } finally {
            if ($previous === false) {
                putenv('PASSPORT_EXTENDED_TOKENS');
            } else {
                putenv("PASSPORT_EXTENDED_TOKENS={$previous}");
            }
            unset($_ENV['PASSPORT_EXTENDED_TOKENS'], $_SERVER['PASSPORT_EXTENDED_TOKENS']);
        }
    }
}
