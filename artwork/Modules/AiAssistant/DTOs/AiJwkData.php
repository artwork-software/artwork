<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein einzelner JSON Web Key (core-jwks; contracts/token-contract.md §JWKS).
 */
class AiJwkData extends Data
{
    public function __construct(
        public string $kty,
        public string $use,
        public string $alg,
        public string $kid,
        public string $n,
        public string $e,
    ) {
    }

    /**
     * @param array<string, string> $key
     */
    public static function fromArray(array $key): self
    {
        return new self(
            kty: $key['kty'],
            use: $key['use'],
            alg: $key['alg'],
            kid: $key['kid'],
            n: $key['n'],
            e: $key['e'],
        );
    }
}
