<?php

namespace Tests\Feature\AiAssistant;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-jwks: GET /api/v1/ai/jwks liefert den oeffentlichen Passport-Schluessel als JWK-Set.
 *
 * Die Tests erzeugen ein eigenes RSA-Paar und setzen es ueber config('passport.public_key') —
 * sie duerfen nicht vom Produktionsschluessel der Instanz abhaengen.
 */
final class JwksControllerTest extends TestCase
{
    private string $privateKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $this->privateKey);
        config(['passport.public_key' => openssl_pkey_get_details($resource)['key']]);

        Cache::forget('ai.jwks');
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(): array
    {
        return $this->getJson(route('api.v1.ai.jwks'))->assertOk()->json();
    }

    #[Test]
    public function it_returns_a_jwk_set_with_exactly_one_rsa_key(): void
    {
        $keys = $this->fetch()['keys'];

        $this->assertCount(1, $keys);
        $this->assertSame('RSA', $keys[0]['kty']);
        $this->assertSame('sig', $keys[0]['use']);
        $this->assertSame('RS256', $keys[0]['alg']);
        $this->assertNotEmpty($keys[0]['kid']);
    }

    /** base64url: kein Padding, keine +/ aus dem Standardalphabet. */
    #[Test]
    public function the_modulus_and_exponent_are_base64url_encoded(): void
    {
        $key = $this->fetch()['keys'][0];

        foreach (['n', 'e'] as $field) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key[$field]);
        }

        $this->assertSame('AQAB', $key['e'], 'Der uebliche Exponent 65537');
    }

    /**
     * Der eigentliche Zweck des Endpunkts: Ein mit dem privaten Schluessel signiertes Token muss
     * sich allein aus n und e der Antwort verifizieren lassen — der Weg von artwork-ai.
     *
     * Bewusst ueber den einzelnen Key (JWK::parseKey) und nicht ueber das Key-Set: Passport-Tokens
     * tragen kein kid im Header, ein kid-basierter Lookup ueber JWK::parseKeySet wuerde deshalb mit
     * '"kid" empty, unable to lookup correct key' scheitern. Siehe
     * passport_tokens_carry_no_key_id_header() weiter unten.
     */
    #[Test]
    public function a_token_signed_with_the_private_key_verifies_against_the_published_key(): void
    {
        $jwt = JWT::encode(['sub' => '42', 'exp' => time() + 60], $this->privateKey, 'RS256');

        $decoded = JWT::decode($jwt, JWK::parseKey($this->fetch()['keys'][0], 'RS256'));

        $this->assertSame('42', $decoded->sub);
    }

    /**
     * Festgehalten, weil artwork-ai darauf angewiesen ist: Weder Session-Tokens noch
     * Personal-Access-Tokens tragen ein kid. Das JWK-Set veroeffentlicht eines, aber es dient der
     * Schluesselrotation, nicht der Zuordnung — der Verifizierer muss den einzigen Key des Sets
     * nehmen, statt per kid zu suchen.
     */
    #[Test]
    public function passport_tokens_carry_no_key_id_header(): void
    {
        $jwt = JWT::encode(['sub' => '42', 'exp' => time() + 60], $this->privateKey, 'RS256');
        $header = json_decode(base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/')), true);

        $this->assertArrayNotHasKey('kid', $header);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('"kid" empty');

        JWT::decode($jwt, JWK::parseKeySet($this->fetch(), 'RS256'));
    }

    #[Test]
    public function a_token_signed_with_a_foreign_key_does_not_verify(): void
    {
        $foreign = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($foreign, $foreignPrivateKey);

        $jwt = JWT::encode(['sub' => '42', 'exp' => time() + 60], $foreignPrivateKey, 'RS256');

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);

        JWT::decode($jwt, JWK::parseKey($this->fetch()['keys'][0], 'RS256'));
    }

    #[Test]
    public function the_key_id_is_stable_across_requests(): void
    {
        $first = $this->fetch()['keys'][0]['kid'];
        Cache::forget('ai.jwks');
        $second = $this->fetch()['keys'][0]['kid'];

        $this->assertSame($first, $second);
        $this->assertSame(16, strlen($first));
    }

    #[Test]
    public function a_different_key_yields_a_different_key_id(): void
    {
        $first = $this->fetch()['keys'][0]['kid'];

        $other = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        config(['passport.public_key' => openssl_pkey_get_details($other)['key']]);
        Cache::forget('ai.jwks');

        $this->assertNotSame($first, $this->fetch()['keys'][0]['kid']);
    }

    #[Test]
    public function it_is_reachable_without_authentication_and_sets_the_cache_header(): void
    {
        $response = $this->getJson(route('api.v1.ai.jwks'))->assertOk();

        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_sixty_first_request_per_minute_is_throttled(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson(route('api.v1.ai.jwks'))->assertOk();
        }

        $this->getJson(route('api.v1.ai.jwks'))->assertStatus(429);
    }

    /** Unauthentifizierte Abrufe duerfen die API-Logtabelle nicht fuellen. */
    #[Test]
    public function it_does_not_write_api_access_log_entries(): void
    {
        $before = \Artwork\Core\Api\Models\ApiLog::query()->count();

        $this->getJson(route('api.v1.ai.jwks'))->assertOk();

        $this->assertSame($before, \Artwork\Core\Api\Models\ApiLog::query()->count());
    }
}
