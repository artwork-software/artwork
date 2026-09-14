<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use RuntimeException;

/**
 * GET /api/v1/ai/jwks — der oeffentliche Passport-Schluessel dieser Instanz als JWK-Set
 * (core-jwks; Konzept C2 §5, contracts/token-contract.md §JWKS).
 *
 * Damit verifiziert artwork-ai die Session-Tokens eines Hauses, ohne dass beim Onboarding ein PEM
 * von Hand gepflegt werden muss: Es reicht die Instanz-URL. Der Endpunkt ist unauthentifiziert,
 * weil der Schluessel oeffentlich ist — das ist der Zweck eines Public Keys.
 */
class JwksController extends Controller
{
    private const CACHE_KEY = 'ai.jwks';
    private const CACHE_SECONDS = 3600;

    public function __invoke(): JsonResponse
    {
        return response()
            ->json(['keys' => [$this->key()]])
            ->header('Cache-Control', 'public, max-age=' . self::CACHE_SECONDS);
    }

    /**
     * Gecacht wird nur das Key-Array, nicht die Antwort: So haengt der Cache-Control-Header nicht
     * am Cache-Zustand, und die Schluesseldatei wird nicht bei jedem Abruf gelesen.
     *
     * @return array<string, string>
     */
    private function key(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function (): array {
            $pem = $this->publicKeyContents();
            $details = $this->rsaDetails($pem);

            return [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $this->keyId($pem),
                'n' => $this->base64Url($details['n']),
                'e' => $this->base64Url($details['e']),
            ];
        });
    }

    /** Gleiche Aufloesung wie PassportServiceProvider::makeCryptKey('public'). */
    private function publicKeyContents(): string
    {
        $key = str_replace('\\n', "\n", config('passport.public_key') ?? '');

        if ($key !== '') {
            return $key;
        }

        $path = Passport::keyPath('oauth-public.key');
        $contents = is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException('Der oeffentliche Passport-Schluessel ist nicht lesbar.');
        }

        return $contents;
    }

    /**
     * openssl_pkey_get_details() liefert n und e als Binaerstrings, nicht als Hex.
     *
     * @return array{n: string, e: string}
     */
    private function rsaDetails(string $pem): array
    {
        $key = openssl_pkey_get_public($pem);

        if ($key === false) {
            throw new RuntimeException('Der oeffentliche Passport-Schluessel ist kein gueltiger PEM-Schluessel.');
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new RuntimeException('Der oeffentliche Passport-Schluessel ist kein RSA-Schluessel.');
        }

        return ['n' => $details['rsa']['n'], 'e' => $details['rsa']['e']];
    }

    /**
     * kid = base64url(sha256(DER))[0:16]. Der Hash muss roh sein, sonst waeren es 16 Hex-Zeichen
     * statt 16 base64url-Zeichen. Der Wert haengt allein am Schluessel und ist damit stabil,
     * solange der Schluessel nicht rotiert.
     */
    private function keyId(string $pem): string
    {
        $body = preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem) ?? '';
        $der = base64_decode($body, true);

        if ($der === false) {
            throw new RuntimeException('Der oeffentliche Passport-Schluessel laesst sich nicht dekodieren.');
        }

        return substr($this->base64Url(hash('sha256', $der, true)), 0, 16);
    }

    private function base64Url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
