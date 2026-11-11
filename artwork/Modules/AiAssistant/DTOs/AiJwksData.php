<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Das JWK-Set von `GET /api/v1/ai/jwks` (core-jwks).
 *
 * Typisiert, weil scramble aus der untypisierten Antwort ein Fixtupel ableitete
 * (`minItems: 1, maxItems: 1`) — eine Zusage, die eine Schluesselrotation brechen wuerde
 * (Export-Fix 2, 16.09.2026).
 */
class AiJwksData extends Data
{
    /** @param AiJwkData[] $keys */
    public function __construct(
        public array $keys,
    ) {
    }
}
