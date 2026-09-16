<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `POST /ai/session` (core-session-token; Konzept C2 §3).
 *
 * Typisiert, damit der Export die Form nennt: ohne DTO leitet scramble aus den Rueckgabezweigen
 * von `AiSessionService::issue()` feste Tupel ab („genau ai:write + ai:transcript") und schreibt
 * fuer den leeren Zweig ein `"items": []`, das gar kein gueltiges JSON-Schema ist
 * (Export-Fix 2, 16.09.2026).
 */
class AiSessionTokenData extends Data
{
    /**
     * @param string[] $scopes
     */
    public function __construct(
        public string $access_token,
        public string $expires_at,
        public array $scopes,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            access_token: (string) $payload['access_token'],
            expires_at: (string) $payload['expires_at'],
            scopes: array_values((array) $payload['scopes']),
        );
    }
}
