<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine bestaetigungspflichtige Aktion fuer artwork-ai (core-confirm-actions; Konzept W2 §4).
 *
 * Die `jti` des Write-Tokens steht bewusst **nicht** darin: sie bleibt in artwork und ist die
 * Bindung zwischen Token und Aktion — artwork-ai braucht sie nie.
 */
class AiPendingActionData extends Data
{
    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $preview
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $tool,
        public array $args,
        public array $preview,
        public ?string $args_hash,
        public ?string $expires_at,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            status: (string) $payload['status'],
            tool: (string) ($payload['tool'] ?? ''),
            args: (array) ($payload['args'] ?? []),
            preview: (array) ($payload['preview'] ?? []),
            args_hash: isset($payload['args_hash']) ? (string) $payload['args_hash'] : null,
            expires_at: isset($payload['expires_at']) ? (string) $payload['expires_at'] : null,
        );
    }
}
