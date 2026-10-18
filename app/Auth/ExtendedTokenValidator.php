<?php

namespace App\Auth;

use DateTimeImmutable;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Bearer-Validierung für auth:api mit zwei Ergänzungen zu Passports Standard:
 *
 * 1. Ablaufdatum aus der Datenbank: Das in Tooleinstellungen → Schnittstellen gesetzte Ablaufdatum
 *    landet nur in oauth_access_tokens.expires_at, der signierte exp-Claim behält Passports
 *    Standardlaufzeit. Deshalb gilt zusätzlich expires_at — das frühere der beiden Daten gewinnt.
 *
 * 2. Akzeptiert einzelne, per jti konfigurierte Tokens bis zu einer festen Deadline über ihren
 * exp-Claim hinaus. Bewusste, zeitlich begrenzte Ausnahme für Integrationen, deren Gegenseite
 * Tokens nicht rotieren kann — kein allgemeiner Mechanismus zur Laufzeitverlängerung.
 *
 * Signatur- und Revoke-Prüfung bleiben vollständig aktiv: Ein gelistetes Token lässt sich über
 * oauth_access_tokens.revoked = 1 jederzeit sperren. expires_at gilt für gelistete Tokens nicht,
 * dort ist die Deadline maßgeblich. Nicht gelistete Tokens laufen über den Standardpfad.
 * Standardmäßig inaktiv (config passport.extended_tokens leer).
 *
 * Solange eine Ausnahme aktiv ist, darf das Passport-Keypair der Instanz nicht rotiert werden.
 */
final class ExtendedTokenValidator extends BearerTokenValidator
{
    private CryptKeyInterface $verificationKey;

    /** @param array<string, string> $extendedTokens jti => Deadline (z. B. "2027-06-30 23:59:59") */
    public function __construct(
        private readonly AccessTokenRepositoryInterface $tokens,
        private readonly array $extendedTokens,
    ) {
        parent::__construct($tokens);
    }

    public function setPublicKey(CryptKeyInterface $key): void
    {
        $this->verificationKey = $key;
        parent::setPublicKey($key);
    }

    public function validateAuthorization(ServerRequestInterface $request): ServerRequestInterface
    {
        $claims = $this->unverifiedClaims($request);
        $jti = $claims['jti'] ?? null;
        $deadline = is_string($jti) ? ($this->extendedTokens[$jti] ?? null) : null;

        if ($deadline === null || !isset($claims['exp'])) {
            return $this->validateStandard($request);
        }

        try {
            $leeway = (new DateTimeImmutable('@' . (int) $claims['exp']))
                ->diff(new DateTimeImmutable($deadline));
        } catch (Throwable) {
            // Nicht parsebare Deadline: fail closed über den Standardpfad.
            return $this->validateStandard($request);
        }

        if ($leeway->invert) {
            // Deadline vor exp: Die Ausnahme verlängert nichts, Standardpfad.
            return $this->validateStandard($request);
        }

        // Frische Instanz, damit der Leeway ausschließlich für dieses Token gilt — nie global.
        // exp + Leeway = Deadline; Signatur- und Revoke-Prüfung laufen dort vollständig.
        $validator = new BearerTokenValidator($this->tokens, $leeway);
        $validator->setPublicKey($this->verificationKey);

        return $validator->validateAuthorization($request);
    }

    /**
     * Passports Standardprüfung (Signatur, exp, revoked) plus das Ablaufdatum aus der Datenbank.
     *
     * @throws OAuthServerException
     */
    private function validateStandard(ServerRequestInterface $request): ServerRequestInterface
    {
        $validated = parent::validateAuthorization($request);

        $token = Passport::token()->newQuery()
            ->whereKey((string) $validated->getAttribute('oauth_access_token_id'))
            ->first(['id', 'expires_at']);

        if ($token?->expires_at?->isPast()) {
            throw OAuthServerException::accessDenied('Access token has expired');
        }

        return $validated;
    }

    /**
     * Nur zur Pfadauswahl — NICHT verifiziert, nie für Autorisierungsentscheidungen verwenden.
     *
     * @return array<string, mixed>
     */
    private function unverifiedClaims(ServerRequestInterface $request): array
    {
        $jwt = preg_replace('/^\s*Bearer\s+/i', '', $request->getHeaderLine('authorization'));
        $payload = explode('.', (string) $jwt)[1] ?? '';
        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);

        return is_array($claims) ? $claims : [];
    }
}
