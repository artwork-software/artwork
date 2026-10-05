<?php

namespace App\Auth;

use DateTimeImmutable;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Akzeptiert einzelne, per jti konfigurierte Tokens bis zu einer festen Deadline über ihren
 * exp-Claim hinaus. Bewusste, zeitlich begrenzte Ausnahme für Integrationen, deren Gegenseite
 * Tokens nicht rotieren kann — kein allgemeiner Mechanismus zur Laufzeitverlängerung.
 *
 * Signatur- und Revoke-Prüfung bleiben vollständig aktiv: Ein gelistetes Token lässt sich über
 * oauth_access_tokens.revoked = 1 jederzeit sperren. Nicht gelistete Tokens laufen unverändert
 * über den Standardpfad. Standardmäßig inaktiv (config passport.extended_tokens leer).
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
            return parent::validateAuthorization($request);
        }

        try {
            $leeway = (new DateTimeImmutable('@' . (int) $claims['exp']))
                ->diff(new DateTimeImmutable($deadline));
        } catch (Throwable) {
            // Nicht parsebare Deadline: fail closed über den Standardpfad.
            return parent::validateAuthorization($request);
        }

        if ($leeway->invert) {
            // Deadline vor exp: Die Ausnahme verlängert nichts, Standardpfad.
            return parent::validateAuthorization($request);
        }

        // Frische Instanz, damit der Leeway ausschließlich für dieses Token gilt — nie global.
        // exp + Leeway = Deadline; Signatur- und Revoke-Prüfung laufen dort vollständig.
        $validator = new BearerTokenValidator($this->tokens, $leeway);
        $validator->setPublicKey($this->verificationKey);

        return $validator->validateAuthorization($request);
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
